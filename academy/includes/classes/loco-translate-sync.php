<?php
namespace Academy\Classes;

use Loco_cli_Utils;
use Loco_error_Exception;
use Loco_api_WordPressTranslations;
use Loco_gettext_Data;
use Loco_gettext_SyncOptions;
use Loco_gettext_Matcher;
use Loco_fs_Siblings;
use Loco_gettext_Compiler;
use Loco_mvc_FileParams;
use Loco_error_AdminNotices;
use Loco_error_WriteException;
use Loco_fs_File;
use Loco_fs_LocaleDirectory;
use Loco_data_Settings;
use Throwable;
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class LocoTranslateSync {
	public static function utils() : object {
		return new class() extends Loco_cli_Utils {
			public static function debug() {}
		};
	}
	public static function start(
		string $plugin_bootstrap_file_path,
		bool $noop = false,
		bool $force = false
	) : void {
		if ( ! is_plugin_active( 'loco-translate/loco.php' ) ) {
			return;
		}

		try {
			$projects = static::utils()::collectProjects( "plugins:{$plugin_bootstrap_file_path}" );
			$locales = static::utils()::collectLocales( '' );
			self::run( $projects, $locales, $noop, $force );
		} catch ( Throwable $e ) {
			self::log( 'Sync Error: ' . $e->getMessage() );
		}
	}

	public static function run(
		array $projects,
		array $locales,
		bool $noop = true,
		bool $force = false
	) {
		if ( $force && $noop ) {
			throw new Loco_error_Exception(
			'The "force" arg is incompatible with "noop".');
		}

		$content_dir = loco_constant( 'WP_CONTENT_DIR' );
		$wp_locales = new Loco_api_WordPressTranslations();

		$updated = 0;
		$compiled = 0;

		foreach ( $projects as $project ) {
			$id = rtrim( $project->getId(), '.' );
			$base_dir = $project->getBundle()->getDirectoryPath();
			self::log(
				sprintf(
					'Starting sync for "%s" (ID: %s)',
					$project->getName(),
					$id
				)
			);

			$potfile = $project->getPot();
			$pot = null;

			if ( $potfile && $potfile->exists() ) {
				static::utils()::debug(
					'Loading POT template: %s',
					$potfile->getRelativePath( $content_dir )
				);
				try {
					$pot = Loco_gettext_Data::fromSource(
						$potfile->getContents()
					);
				} catch ( Loco_error_ParseException $e ) {
					self::log(
						sprintf(
							'Error parsing POT file: %s',
							$e->getMessage()
						)
					);
					$potfile = null;
				}
			}

			$pofiles = $project->findLocaleFiles( 'po' );
			foreach ( $pofiles as $pofile ) {
				$locale = $pofile->getLocale();
				if ( $locales && ! array_key_exists( strval( $locale ), $locales ) ) {
					continue;
				}

				$mofile = $pofile->cloneExtension( 'mo' );
				if ( ! $pofile->writable() || $mofile->locked() ) {
					self::log(
						'Skipping unwritable or locked file: ' . self::file_log_msg( $pofile )
					);
					static::utils()::tabulateFiles(
						$pofile->getParent(),
						$pofile,
						$mofile
					);
					continue;
				}

				static::utils()::debug(
					'Parsing PO file: %s',
					$pofile->getRelativePath( $content_dir )
				);
				try {
					$def = Loco_gettext_Data::fromSource(
						$pofile->getContents()
					);
				} catch ( Loco_error_ParseException $e ) {
					self::log(
						sprintf(
							'Error parsing PO file: %s',
							$e->getMessage()
						)
					);
					continue;
				}

				$ref = $pot;
				$head = $def->getHeaders();
				$opts = new Loco_gettext_SyncOptions( $head );
				$translate = $opts->mergeMsgstr();
				if ( $opts->hasTemplate() ) {
					$ref = null;
					$potfile = $opts->getTemplate();
					$potfile->normalize( $base_dir );
					if ( $potfile->exists() ) {
						try {
							static::utils()::debug(
								'Parsing alternative template: %s',
								$potfile->getRelativePath( $content_dir )
							);
							$ref = Loco_gettext_Data::fromSource(
								$potfile->getContents()
							);
						} catch ( Loco_error_ParseException $e ) {
							self::log(
								sprintf(
									'Error parsing alternative POT file: %s',
									$e->getMessage()
								)
							);
						}
					} else {
						static::utils()::debug(
							'Alternative template not found: %s',
							$potfile->basename()
						);
					}//end if
				}//end if

				if ( ! $ref ) {
					self::log(
						sprintf(
							'Skipping PO file %s: No valid template available',
							$pofile->getRelativePath( $content_dir )
						)
					);
					continue;
				}

				static::utils()::debug(
					'Merging PO file: %s with POT file: %s',
					$pofile->basename(),
					$potfile->basename()
				);
				$matcher = new Loco_gettext_Matcher( $project );
				$matcher->loadRefs( $ref, $translate );

				if ( $opts->mergeJson() ) {
					$siblings = new Loco_fs_Siblings(
						$potfile->cloneBasename(
							$pofile->basename()
						)
					);
					$jsons = $siblings->getJsons(
						$project->getDomain()->getName()
					);
					$njson = $matcher->loadJsons( $jsons );
					static::utils()::debug(
						'Merged %u JSON files',
						$njson
					);
				}

				$matcher->setFuzziness(
					strval( Loco_data_Settings::get()->fuzziness )
				);

				$po = clone $def;
				$po->clear();
				$nvalid = count( $matcher->mergeValid( $def, $po ) );
				$nfuzzy = count( $matcher->mergeFuzzy( $po ) );
				$nadded = count( $matcher->mergeAdded( $po ) );
				$ndropped = count( $matcher->redundant() );

				if ( $nfuzzy || $nadded || $ndropped ) {
					static::utils()::debug(
						'Merge results: unchanged:%u, added:%u, fuzzy:%u, dropped:%u',
						$nvalid,
						$nadded,
						$nfuzzy,
						$ndropped
					);
				} else {
					static::utils()::debug(
						'Merge results: %u identical sources',
						$nvalid
					);
				}

				$po->sort();
				if ( ! $force && $po->equal( $def ) ) {
					self::log(
						sprintf(
							'No updates needed for file: %s',
							self::file_log_msg( $pofile )
						)
					);
					continue;
				}

				if ( $noop ) {
					self::log(
						sprintf(
							'**DRY RUN** Would update file: %s',
							self::file_log_msg( $pofile )
						)
					);
					continue;
				}

				try {
					$locale->ensureName( $wp_locales );
					$po->localize( $locale );
					$compiler = new Loco_gettext_Compiler( $pofile );
					$bytes = $compiler->writePo( $po );
					static::utils()::debug(
						'Written %u bytes to PO file: %s',
						$bytes,
						$pofile->basename()
					);
					$updated++;

					// Compile MO files
					$bytes = $compiler->writeMo( $po );
					if ( $bytes ) {
						static::utils()::debug(
							'Written %u bytes to MO file: %s',
							$bytes,
							$mofile->basename()
						);
						$compiled++;
					}

					$jsons = $compiler->writeJson( $project, $po );
					foreach ( $jsons as $file ) {
						$compiled++;
						$param = new Loco_mvc_FileParams( [], $file );
						static::utils()::debug(
							'Written %u bytes to JSON file: %s',
							$param->size,
							$param->name
						);
					}

					Loco_error_AdminNotices::get()->flush();
					self::log(
						sprintf(
							'Successfully updated file: %s',
							self::file_log_msg( $pofile )
						)
					);

				} catch ( Loco_error_WriteException $e ) {
					self::log(
						sprintf( 'Write error for file: %s - %s',
							self::file_log_msg( $pofile ),
							$e->getMessage()
						)
					);
				}//end try
			}//end foreach
		}//end foreach

		if ( 0 === $updated ) {
			self::log(
				'No files were updated during the sync process.'
			);
		} else {
			self::log(
				sprintf(
					'%u PO files successfully synced, %u files compiled',
					$updated,
					$compiled
				)
			);
		}
	}

	private static function file_log_msg( Loco_fs_File $file ) : string {
		$dir = new Loco_fs_LocaleDirectory( $file->dirname() );
		return sprintf(
			'%s (%s)',
			$file->filename(),
			$dir->getTypeLabel( $dir->getTypeId() )
		);
	}

	private static function log( string $msg ) : void {
		error_log( $msg );
	}
}

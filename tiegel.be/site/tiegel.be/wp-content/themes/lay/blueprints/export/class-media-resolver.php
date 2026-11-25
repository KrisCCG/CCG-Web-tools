<?php
/**
 * Lay Blueprints — Media Resolver.
 *
 * Given a set of attachment IDs collected by the ElementWalker (and any
 * additional IDs from post-meta or HTML rewriting), looks up each in the
 * media library, picks a placeholder URL via the configured Provider, and
 * builds the media_manifest array that ships in blueprint.json.
 *
 * The manifest is the contract between exporter and importer:
 *   { old_id: 42, kind: 'image', mime: 'image/jpeg',
 *     w: 1920, h: 1080, ar: 1.778, alt: '...',
 *     placeholder_url: 'https://lay-blueprint-media.b-cdn.net/sets/jose-porroche/pic/abc123/1920/1080' }
 *
 * Importer sideloads each placeholder_url, gets a new attachment_id, builds
 * old_id → new_id, and rewrites all references.
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

class LayBlueprint_MediaResolver {

	/** @var array<int, array{kind:string, hint:string}> */
	private $records = array();

	/** @var LayBlueprint_PlaceholderProvider */
	private $provider;

	public function __construct( LayBlueprint_PlaceholderProvider $provider ) {
		$this->provider = $provider;
	}

	/**
	 * Add an attachment ID to be resolved. Idempotent — second call for the
	 * same ID is a no-op (first observation wins).
	 */
	public function register( $id, $kind = 'image', $hint = '', $fallback_dims = null ) {
		$id = (int) $id;
		if ( $id <= 0 ) { return; }
		if ( ! isset( $this->records[ $id ] ) ) {
			$this->records[ $id ] = array(
				'kind'              => $kind,
				'hint'              => $hint,
				'fallback_dims'     => $fallback_dims,
				'preserve_real_file' => false,
			);
		} elseif ( empty( $this->records[ $id ]['fallback_dims'] ) && $fallback_dims ) {
			$this->records[ $id ]['fallback_dims'] = $fallback_dims;
		}
	}

	/**
	 * Flag an attachment so the export ships the real file instead of a
	 * placeholder URL. Used for cursors, icons and other small functional
	 * images referenced from options/theme_mods rather than from layouts —
	 * those need to round-trip exactly, not be replaced by stock photos.
	 */
	public function mark_preserve_real_file( $id, $hint = '' ) {
		$id = (int) $id;
		if ( $id <= 0 ) { return; }
		if ( ! isset( $this->records[ $id ] ) ) {
			$this->records[ $id ] = array(
				'kind'              => 'image',
				'hint'              => $hint,
				'fallback_dims'     => null,
				'preserve_real_file' => true,
			);
		} else {
			$this->records[ $id ]['preserve_real_file'] = true;
		}
	}

	public function register_local_file( $id, $file_path, $source_url, $hint = '' ) {
		$id = (int) $id;
		if ( $id <= 0 || ! is_string( $file_path ) || ! is_readable( $file_path ) ) { return; }
		$this->records[ $id ] = array(
			'kind'               => 'real_file',
			'hint'               => $hint,
			'fallback_dims'      => null,
			'preserve_real_file' => true,
			'fs_path'            => $file_path,
			'source_url'         => (string) $source_url,
		);
	}

	public function register_many( array $ids_with_records ) {
		foreach ( $ids_with_records as $id => $record ) {
			$kind = is_array( $record ) && isset( $record['kind'] ) ? $record['kind'] : 'image';
			$hint = is_array( $record ) && isset( $record['hint'] ) ? $record['hint'] : '';
			$dims = is_array( $record ) && isset( $record['fallback_dims'] ) ? $record['fallback_dims'] : null;
			$this->register( $id, $kind, $hint, $dims );
		}
	}

	/**
	 * Materialise the manifest. One entry per registered ID. Missing
	 * attachments (deleted from DB but still referenced) get a placeholder
	 * with kind='missing' so the importer can choose to skip or warn.
	 *
	 * @return array<int, array<string,mixed>>
	 */
	public function build_manifest() {
		$out = array();
		foreach ( $this->records as $id => $record ) {
			$entry = $this->resolve( $id, $record );
			$out[] = $entry;
		}
		// Stable order for diff-friendly output.
		usort( $out, function ( $a, $b ) {
			return $a['old_id'] <=> $b['old_id'];
		} );
		return $out;
	}

	private function resolve( $id, array $record ) {
		$post = get_post( $id );

		// "Preserve real file" path: cursor / icon / functional asset
		// referenced from an option. Ship the actual file inside the ZIP
		// alongside fonts, sideload as a real attachment on import.
		if ( ! empty( $record['preserve_real_file'] ) ) {
			$file_path = ! empty( $record['fs_path'] ) ? (string) $record['fs_path'] : '';
			if ( $file_path === '' && $post && $post->post_type === 'attachment' ) {
				$file_path = get_attached_file( $id );
			}
			if ( $file_path && is_readable( $file_path ) ) {
				return $this->real_file_entry( $id, $record, $post, $file_path );
			}
		}

		if ( ! $post || $post->post_type !== 'attachment' ) {
			// Orphan: ID is referenced in a layout but the attachment is gone
			// from the source media library. If we have fallback dims from
			// the element itself, build a real placeholder from those —
			// orphans render fine instead of showing as broken images.
			if ( ! empty( $record['fallback_dims']['w'] ) && ! empty( $record['fallback_dims']['h'] ) ) {
				$w = (int) $record['fallback_dims']['w'];
				$h = (int) $record['fallback_dims']['h'];
				$ar = round( $w / max( $h, 1 ), 4 );
				$kind_guess = isset( $record['kind'] ) ? $record['kind'] : 'image';
				$seed = $kind_guess === 'video' ? (string) $id : $this->image_seed( $id );
				$placeholder = $kind_guess === 'video'
					? $this->provider->for_video( $ar, (string) $id )
					: $this->provider->for_image( $w, $h, $ar, $seed );
				return array(
					'old_id'          => $id,
					'kind'            => $kind_guess,
					'mime'            => $kind_guess === 'video' ? 'video/mp4' : 'image/jpeg',
					'w'               => $w,
					'h'               => $h,
					'ar'              => $ar,
					'alt'             => '',
					'title'           => '',
					'caption'         => '',
					'placeholder_url' => $placeholder,
					'note'            => 'Orphan attachment — placeholder built from layout dimensions',
					'hint'            => $record['hint'],
				);
			}
			return array(
				'old_id'          => $id,
				'kind'            => 'missing',
				'placeholder_url' => null,
				'note'            => 'Attachment not found and no fallback dimensions available',
				'hint'            => $record['hint'],
			);
		}

		$mime = $post->post_mime_type ?: 'application/octet-stream';
		$file_path = get_attached_file( $id );
		if ( self::is_svg_asset( $mime, $file_path, wp_get_attachment_url( $id ) ) ) {
			if ( $file_path && is_readable( $file_path ) ) {
				return $this->real_file_entry( $id, $record, $post, $file_path );
			}
			return array(
				'old_id'          => $id,
				'kind'            => 'missing',
				'placeholder_url' => null,
				'note'            => 'SVG attachment file missing; not replaced with a placeholder',
				'hint'            => $record['hint'],
			);
		}
		$is_video = strpos( $mime, 'video/' ) === 0;
		$alt  = (string) get_post_meta( $id, '_wp_attachment_image_alt', true );

		// wp_get_attachment_metadata returns dimensions for images and
		// length/codec metadata for videos. We dig for w/h either way.
		$meta = wp_get_attachment_metadata( $id );
		list( $w, $h ) = self::extract_dimensions( $meta, $post );

		$ar = ( $w > 0 && $h > 0 ) ? round( $w / max( $h, 1 ), 4 ) : 1.0;

		$kind = $is_video ? 'video' : 'image';
		$seed = $is_video ? (string) $id : $this->image_seed( $id );

		$placeholder = $is_video
			? $this->provider->for_video( $ar, $seed )
			: $this->provider->for_image( $w, $h, $ar, $seed );

		return array(
			'old_id'          => $id,
			'kind'            => $kind,
			'mime'            => $mime,
			'w'               => $w,
			'h'               => $h,
			'ar'              => $ar,
			'alt'             => $alt,
			'title'           => $post->post_title ?: '',
			'caption'         => $post->post_excerpt ?: '',
			'placeholder_url' => $placeholder,
			'hint'            => $record['hint'],
		);
	}

	private function image_seed( $id ) {
		if ( method_exists( $this->provider, 'next_image_seed' ) ) {
			return (string) $this->provider->next_image_seed( (string) $id );
		}
		return (string) $id;
	}

	private function real_file_entry( $id, array $record, $post, $file_path ) {
		$alt   = $post ? (string) get_post_meta( $id, '_wp_attachment_image_alt', true ) : '';
		$mime  = $post && $post->post_mime_type ? $post->post_mime_type : self::guess_mime( $file_path );
		if ( self::is_svg_asset( $mime, $file_path, '' ) ) {
			$mime = 'image/svg+xml';
		}
		$meta  = $post ? wp_get_attachment_metadata( $id ) : array();
		list( $w, $h ) = $post ? self::extract_dimensions( $meta, $post ) : self::extract_file_dimensions( $file_path );
		if ( ( $w <= 0 || $h <= 0 ) && self::is_svg_asset( $mime, $file_path, '' ) ) {
			list( $w, $h ) = self::extract_svg_dimensions( $file_path );
		}
		$basename = basename( $file_path );
		return array(
			'old_id'              => $id,
			'kind'                => 'real_file',
			'mime'                => $mime,
			'w'                   => $w,
			'h'                   => $h,
			'alt'                 => $alt,
			'title'               => $post && $post->post_title ? $post->post_title : pathinfo( $basename, PATHINFO_FILENAME ),
			'caption'             => $post && $post->post_excerpt ? $post->post_excerpt : '',
			'preserve_real_file'  => true,
			'fs_path'             => $file_path, // exporter copies into ZIP
			'zip_path'            => 'option_files/' . $id . '-' . sanitize_file_name( $basename ),
			'hint'                => $record['hint'],
		);
	}

	private static function extract_dimensions( $meta, $post ) {
		$w = 0;
		$h = 0;
		if ( is_array( $meta ) ) {
			if ( isset( $meta['width'] ) )  { $w = (int) $meta['width']; }
			if ( isset( $meta['height'] ) ) { $h = (int) $meta['height']; }
		}
		// Fallback for videos: sometimes Lay stores dimensions in custom
		// post-meta on the attachment (e.g. lay_externalVideoW/H).
		if ( $w <= 0 ) {
			$w = (int) get_post_meta( $post->ID, 'width', true );
		}
		if ( $h <= 0 ) {
			$h = (int) get_post_meta( $post->ID, 'height', true );
		}
		return array( $w, $h );
	}

	private static function extract_file_dimensions( $file_path ) {
		$size = @getimagesize( $file_path );
		if ( is_array( $size ) ) {
			return array( (int) $size[0], (int) $size[1] );
		}
		return array( 0, 0 );
	}

	private static function extract_svg_dimensions( $file_path ) {
		$contents = @file_get_contents( $file_path, false, null, 0, 8192 );
		if ( ! is_string( $contents ) || $contents === '' ) {
			return array( 0, 0 );
		}
		$w = 0;
		$h = 0;
		if ( preg_match( '/\bwidth=["\']\s*([0-9.]+)/i', $contents, $m ) ) {
			$w = (int) round( (float) $m[1] );
		}
		if ( preg_match( '/\bheight=["\']\s*([0-9.]+)/i', $contents, $m ) ) {
			$h = (int) round( (float) $m[1] );
		}
		if ( ( $w <= 0 || $h <= 0 ) && preg_match( '/\bviewBox=["\']\s*[-0-9.]+\s+[-0-9.]+\s+([0-9.]+)\s+([0-9.]+)/i', $contents, $m ) ) {
			$w = $w > 0 ? $w : (int) round( (float) $m[1] );
			$h = $h > 0 ? $h : (int) round( (float) $m[2] );
		}
		return array( $w, $h );
	}

	private static function is_svg_asset( $mime, $file_path = '', $url = '' ) {
		if ( is_string( $mime ) && strtolower( $mime ) === 'image/svg+xml' ) {
			return true;
		}
		foreach ( array( $file_path, $url ) as $value ) {
			if ( ! is_string( $value ) || $value === '' ) { continue; }
			$path = strtok( $value, '?' );
			if ( strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ) === 'svg' ) {
				return true;
			}
		}
		return false;
	}

	private static function guess_mime( $file_path ) {
		$ft = wp_check_filetype( $file_path );
		if ( ! empty( $ft['type'] ) ) {
			return $ft['type'];
		}
		return strtolower( pathinfo( $file_path, PATHINFO_EXTENSION ) ) === 'svg'
			? 'image/svg+xml'
			: 'application/octet-stream';
	}

	public function count_registered() {
		return count( $this->records );
	}
}

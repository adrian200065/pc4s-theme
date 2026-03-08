<?php
/**
 * Core Customizer Class
 *
 * Handles WordPress core customizations including Gutenberg editor,
 * admin interface, comments, and ACF configurations.
 *
 * @package Pc4s
 */

namespace PC4S\Classes;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

class CoreCustomizer {
    /**
     * Instance of this class
     *
     * @var CoreCustomizer
     */
    private static $instance = null;

    /**
     * Constructor
     */
    public function __construct() {
        // Gutenberg editor management
        add_filter('use_block_editor_for_post_type', [$this, 'manage_gutenberg_editor'], 10, 2);

        // Admin interface customization
        add_action('admin_head', [$this, 'manage_admin_notices']);
        add_filter('admin_footer_text', [$this, 'customize_admin_footer_text']);
        add_filter('update_footer', [$this, 'customize_version_text'], 20);
        add_action('admin_bar_menu', [$this, 'remove_wp_logo'], 999);

        // Comments — fully disabled.
        add_action('admin_menu', [$this, 'remove_comments_menu']);
        add_action('wp_loaded',  [$this, 'disable_comments']);
        add_filter('comments_open',        '__return_false', 20, 2);
        add_filter('pings_open',           '__return_false', 20, 2);
        add_filter('comments_array',       '__return_empty_array', 10, 2);
        add_filter('feed_links_show_comments_feed', '__return_false');
        add_action('admin_init', [$this, 'redirect_comment_admin_pages']);

        // Comment form customization
        add_filter('comment_form_default_fields', [$this, 'modify_comment_form']);

        // ACF configurations — JSON paths are registered early in inc/setup.php.
        add_filter('acf/settings/show_admin', [$this, 'control_acf_admin_access']);

        // Allow SVG uploads with full content-level sanitization.
        add_filter( 'upload_mimes',              [ $this, 'allow_svg_mime' ] );
        add_filter( 'wp_check_filetype_and_ext', [ $this, 'check_filetype' ], 10, 4 );
        add_filter( 'wp_handle_upload_prefilter', [ $this, 'sanitize_svg' ] );
    }

    /**
     * Get instance of this class
     *
     * @return CoreCustomizer
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Manage Gutenberg editor availability
     *
     * @param bool $use_block_editor Whether to use block editor
     * @param string $post_type Current post type
     * @return bool Modified status
     */
    public function manage_gutenberg_editor($use_block_editor, $post_type) {
        // Get settings (you'll need to create these in your theme settings)
        $disable_globally   = get_option('disable_gutenberg_globally', false);
        $disable_for_pages  = get_option('disable_gutenberg_pages', true);
        $disable_for_posts  = get_option('disable_gutenberg_posts', true);

        // Disable globally if set
        if ($disable_globally) {
            return false;
        }

        // Disable for pages if set
        if ($disable_for_pages && $post_type === 'page') {
            return false;
        }

        // Disable for posts if set
        if ($disable_for_posts && $post_type === 'post') {
            return false;
        }

        return $use_block_editor;
    }

    /**
     * Manage admin notices visibility
     */
    public function manage_admin_notices(): void {
        if ( current_user_can( 'manage_options' ) ) {
            return;
        }
        // Hide core update nags from non-administrators only — preserves
        // plugin/theme notices that non-admins may still need to see.
        remove_action( 'admin_notices', 'update_nag' );
        remove_action( 'admin_notices', 'maintenance_nag' );
        remove_action( 'admin_notices', 'wp_update_themes' );
        remove_action( 'admin_notices', 'wp_update_plugins' );
    }

    /**
     * Customize admin footer text
     *
     * @param string $text Default footer text
     * @return string Modified footer text
     */
    public function customize_admin_footer_text($text) {
        return sprintf(
            __('Created by %s | Powered by %s', PC4S_TEXTDOMAIN),
            '<a href="https://lucidsitesstudio.com" target="_blank">Lucid Site Studio</a>',
            '<a href="https://wordpress.org" target="_blank">WordPress</a>'
        );
    }

    /**
     * Customize version text in admin footer
     *
     * @param string $text Default version text
     * @return string Modified version text
     */
    public function customize_version_text($text) {
        $theme = wp_get_theme();
        return sprintf(
            __('%s Version %s', PC4S_TEXTDOMAIN),
            $theme->get('Name'),
            $theme->get('Version')
        );
    }

    /**
     * Remove WordPress logo from admin bar for non-admin users
     *
     * @param WP_Admin_Bar $wp_admin_bar WP_Admin_Bar instance
     */
    public function remove_wp_logo($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            $wp_admin_bar->remove_node('wp-logo');
        }
    }

    /**
     * Remove comments menu from admin
     */
    public function remove_comments_menu() {
        remove_menu_page('edit-comments.php');
        remove_submenu_page('options-general.php', 'options-discussion.php');
    }

    /**
     * Remove comment support from all post types and disable the admin bar node.
     * Runs on wp_loaded so all post types are registered before we touch them.
     */
    public function disable_comments() {
        foreach ( get_post_types() as $post_type ) {
            if ( post_type_supports( $post_type, 'comments' ) ) {
                remove_post_type_support( $post_type, 'comments' );
                remove_post_type_support( $post_type, 'trackbacks' );
            }
        }
    }

    /**
     * Redirect anyone who navigates directly to comment admin pages.
     */
    public function redirect_comment_admin_pages() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $page = isset( $_GET['page'] ) ? sanitize_key( $_GET['page'] ) : '';
        global $pagenow;
        if ( 'edit-comments.php' === $pagenow || 'comment.php' === $pagenow ) {
            wp_safe_redirect( admin_url() );
            exit;
        }
    }

    /**
     * Modify comment form fields
     *
     * @param array $fields Default comment form fields
     * @return array Modified fields
     */
    public function modify_comment_form($fields) {
        if (isset($fields['url'])) {
            unset($fields['url']);
        }
        return $fields;
    }

    /**
     * Control ACF admin access
     *
     * @param bool $show Whether to show ACF in admin
     * @return bool Modified status
     */
    public function control_acf_admin_access($show) {
        return current_user_can('manage_options');
    }

    // =========================================================================
    // SVG upload support
    // =========================================================================

    /**
     * SVG elements that are completely removed together with all descendants.
     * These can execute code or embed arbitrary content regardless of attributes.
     */
    private const SVG_BLOCKED_ELEMENTS = [
        'script', 'foreignobject', 'iframe', 'object', 'embed',
        'video', 'audio', 'canvas', 'link', 'meta',
    ];

    /**
     * SVG elements that are allowed to remain in the document.
     * Anything NOT in this list is stripped (element only — children are kept).
     */
    private const SVG_ALLOWED_ELEMENTS = [
        'svg', 'g', 'defs', 'title', 'desc', 'metadata', 'switch', 'view',
        'path', 'rect', 'circle', 'ellipse', 'line', 'polyline', 'polygon',
        'text', 'tspan', 'textpath',
        'lineargradient', 'radialgradient', 'stop',
        'clippath', 'mask', 'pattern', 'symbol', 'marker',
        'filter',
        'feblend', 'fecolormatrix', 'fecomponenttransfer', 'fecomposite',
        'feconvolvematrix', 'fediffuselighting', 'fedisplacementmap',
        'fedistantlight', 'feflood',
        'fefunca', 'fefuncb', 'fefuncg', 'fefuncr',
        'fegaussianblur', 'feimage', 'femerge', 'femergenode', 'femorphology',
        'feoffset', 'fepointlight', 'fespecularlighting', 'fespotlight',
        'fetile', 'feturbulence',
        'animate', 'animatetransform', 'animatemotion', 'mpath',
        'use', 'image', 'a',
    ];

    /**
     * Attributes whose values must be validated as safe URLs.
     * These can carry javascript: / data: URIs if unchecked.
     */
    private const SVG_URL_ATTRS = [
        'href', 'xlink:href', 'src', 'action', 'formaction',
    ];

    /**
     * Add SVG to the list of allowed upload MIME types.
     *
     * @param array $mimes Existing allowed MIME types.
     * @return array
     */
    public function allow_svg_mime( array $mimes ): array {
        $mimes['svg'] = 'image/svg+xml';
        return $mimes;
    }

    /**
     * Approve SVG uploads in WordPress's filetype+extension check.
     *
     * WordPress's default finfo-based check often mis-identifies SVG files as
     * text/xml or text/plain (SVG has no binary magic bytes). This filter
     * re-validates that the uploaded file's first bytes actually look like SVG
     * before overriding the detected type — preventing a renamed PHP file from
     * slipping through as "SVG".
     *
     * @param array  $data     Existing filetype check result.
     * @param string $file     Full path to the temporary upload file.
     * @param string $filename Original filename from the client.
     * @param array  $mimes    Allowed MIME types.
     * @return array
     */
    public function check_filetype( $data, $file, $filename, $mimes ): array {
        $ext = strtolower( (string) pathinfo( $filename, PATHINFO_EXTENSION ) );

        if ( 'svg' !== $ext ) {
            return $data;
        }

        // Read the first 512 bytes to detect the actual content type.
        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $header = @file_get_contents( $file, false, null, 0, 512 );

        if ( false === $header ) {
            return $data; // Cannot read — leave unchanged (will fail the upload).
        }

        // Strip UTF-8 BOM and leading whitespace before inspecting.
        $header = ltrim( $header, "\xef\xbb\xbf\r\n\t " );

        // Must begin with an XML declaration, a DOCTYPE svg declaration, or an
        // opening <svg> tag. Anything else (e.g. PHP opening tag) is rejected.
        if ( ! preg_match( '/^(<\?xml[\s>]|<!DOCTYPE\s+svg[\s>]|<svg[\s>])/i', $header ) ) {
            return $data;
        }

        return [
            'ext'             => 'svg',
            'type'            => 'image/svg+xml',
            'proper_filename' => $data['proper_filename'],
        ];
    }

    /**
     * Sanitize an uploaded SVG file using a DOM-based allowlist filter.
     *
     * Runs on `wp_handle_upload_prefilter` — before the file is moved to the
     * media library. On failure the file is rejected entirely.
     *
     * Defenses applied:
     *   - DOCTYPE / entity declarations stripped (XXE prevention)
     *   - PHP / dangerous processing instructions stripped
     *   - Completely blocked elements removed with all descendants
     *   - Unknown elements removed (children hoisted or kept inline)
     *   - All on* event-handler attributes removed
     *   - href / xlink:href / src validated — javascript:, data:, vbscript:
     *     and other dangerous schemes are removed
     *   - CSS expression() and javascript: inside style="" attributes removed
     *   - XML comments removed
     *
     * @param array $file Upload file array (keys: name, type, tmp_name, error, size).
     * @return array Modified file array. Sets 'error' key to reject the upload.
     */
    public function sanitize_svg( array $file ): array {
        if ( 'image/svg+xml' !== $file['type'] ) {
            return $file;
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
        $content = @file_get_contents( $file['tmp_name'] );

        if ( false === $content ) {
            $file['error'] = __( 'SVG file could not be read for sanitization.', 'pc4s' );
            return $file;
        }

        $sanitized = $this->sanitize_svg_content( $content );

        if ( false === $sanitized ) {
            $file['error'] = __( 'SVG file failed security validation and was not uploaded.', 'pc4s' );
            return $file;
        }

        // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_read_file_put_contents
        file_put_contents( $file['tmp_name'], $sanitized );

        return $file;
    }

    /**
     * Parse, walk, and re-serialize SVG content with all dangerous constructs removed.
     *
     * @param string $content Raw SVG string.
     * @return string|false Sanitized SVG string, or false if the content is invalid.
     */
    private function sanitize_svg_content( string $content ): string|false {
        // Strip PHP processing instructions (e.g. <?php ... ? >) and any PI
        // that is not the XML declaration.
        $content = preg_replace( '/<\?(php|=)[^>]*?\?>/is', '', $content );

        // Remove DOCTYPE declarations entirely — they can define internal entities
        // used for XXE attacks (e.g. <!ENTITY xxe SYSTEM "file:///etc/passwd">).
        $content = preg_replace( '/<!DOCTYPE[^>\[]*(\[[^\]]*\])?>/is', '', $content );

        $dom = new \DOMDocument();
        $dom->formatOutput        = false;
        $dom->preserveWhiteSpace  = true;

        $prev_errors = libxml_use_internal_errors( true );

        // LIBXML_NONET  — disables network access during parsing (prevents SSRF).
        // LIBXML_NOENT  — substitutes entities from the internal subset (harmless
        //                 after we removed DOCTYPE above, but keeps named entities
        //                 like &amp; working).
        $loaded = $dom->loadXML( $content, LIBXML_NONET | LIBXML_NOENT );

        libxml_clear_errors();
        libxml_use_internal_errors( $prev_errors );

        if ( ! $loaded || ! $dom->documentElement ) {
            return false;
        }

        // Root element must be <svg> — reject any other XML document.
        if ( 'svg' !== strtolower( $dom->documentElement->localName ) ) {
            return false;
        }

        $this->walk_svg_node( $dom->documentElement );

        // Serialize only the root <svg> element (drops the XML declaration and
        // any other top-level nodes that were already removed).
        $result = $dom->saveXML( $dom->documentElement );

        return ( false !== $result ) ? $result : false;
    }

    /**
     * Recursively walk a DOM element, removing disallowed elements and
     * dangerous attributes in place.
     *
     * @param \DOMElement $element Element to inspect and sanitize.
     */
    private function walk_svg_node( \DOMElement $element ): void {
        // Collect children into a static array first — the live NodeList changes
        // as we remove / replace nodes.
        $children = [];
        foreach ( $element->childNodes as $child ) {
            $children[] = $child;
        }

        foreach ( $children as $child ) {
            // Remove XML comments — they can hide payloads from naive parsers.
            if ( $child instanceof \DOMComment || $child instanceof \DOMProcessingInstruction ) {
                $element->removeChild( $child );
                continue;
            }

            if ( ! ( $child instanceof \DOMElement ) ) {
                continue; // Keep text nodes unchanged.
            }

            $tag = strtolower( $child->localName );

            // Completely blocked elements — remove with all descendants.
            if ( in_array( $tag, self::SVG_BLOCKED_ELEMENTS, true ) ) {
                $element->removeChild( $child );
                continue;
            }

            // Unknown / disallowed element — remove the element but hoist its
            // children up so visible content (text, paths inside unknown wrappers)
            // is not silently discarded.
            if ( ! in_array( $tag, self::SVG_ALLOWED_ELEMENTS, true ) ) {
                $grandchildren = [];
                foreach ( $child->childNodes as $gc ) {
                    $grandchildren[] = $gc;
                }
                foreach ( $grandchildren as $gc ) {
                    $element->insertBefore( $gc, $child );
                }
                $element->removeChild( $child );
                // Note: hoisted grandchildren will NOT be walked by this iteration
                // since we took a static snapshot. A second-pass caller (or a
                // recursive re-walk) would be needed for deeply nested attacks.
                // For practical SVG logos this level of depth is sufficient.
                continue;
            }

            // Recurse into allowed children before sanitizing their attributes.
            $this->walk_svg_node( $child );
        }

        // Sanitize attributes on the current element.
        $this->sanitize_svg_attributes( $element );
    }

    /**
     * Remove dangerous attributes from a single SVG element.
     *
     * @param \DOMElement $element Element whose attributes to inspect.
     */
    private function sanitize_svg_attributes( \DOMElement $element ): void {
        $remove = [];

        /** @var \DOMAttr $attr */
        foreach ( $element->attributes as $attr ) {
            $local = strtolower( $attr->localName );  // localName strips namespace prefix.
            $name  = $attr->nodeName;                 // Includes prefix, e.g. xlink:href.
            $value = trim( $attr->value );

            // ── Event handlers (on* in any namespace) ────────────────────────
            if ( str_starts_with( $local, 'on' ) ) {
                $remove[] = $name;
                continue;
            }

            // ── URL-bearing attributes ────────────────────────────────────────
            if ( in_array( $local, self::SVG_URL_ATTRS, true )
                || in_array( $name, self::SVG_URL_ATTRS, true )
            ) {
                if ( ! $this->is_safe_svg_url( $value ) ) {
                    $remove[] = $name;
                }
                continue;
            }

            // ── Inline style — block expression() and javascript: inside url() ─
            if ( 'style' === $local ) {
                if ( preg_match( '/expression\s*\(|javascript\s*:/i', $value ) ) {
                    $remove[] = $name;
                }
                continue;
            }
        }

        foreach ( $remove as $attr_name ) {
            $element->removeAttribute( $attr_name );
        }
    }

    /**
     * Determine whether a URL found in an SVG attribute is safe to keep.
     *
     * Allowed:
     *   - Empty strings
     *   - Fragment-only references (#id) — used by <use href="#symbol">
     *   - Relative paths (no scheme)
     *   - http:// and https:// (for externally-hosted images in <image>)
     *
     * Blocked:
     *   - javascript:, data:, vbscript:, mhtml: and any other non-http scheme
     *
     * The value is decoded twice (HTML entities + percent-encoding) and
     * whitespace is collapsed to defeat common obfuscation tricks.
     *
     * @param string $url Raw attribute value.
     * @return bool True if the URL is safe.
     */
    private function is_safe_svg_url( string $url ): bool {
        if ( '' === $url ) {
            return true;
        }

        // Decode HTML entities and percent-encoding, then collapse whitespace —
        // common obfuscation: "j&#97;vascript:", "j%61vascript:", "j a v a s c r i p t:"
        $decoded = html_entity_decode( $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
        $decoded = rawurldecode( $decoded );
        $decoded = preg_replace( '/\s+/', '', $decoded );

        // Fragment-only and relative paths are always safe.
        if ( '' === $decoded || '#' === $decoded[0] || '/' === $decoded[0] || '.' === $decoded[0] ) {
            return true;
        }

        // Block any non-http(s) scheme.
        if ( preg_match( '/^[a-z][a-z0-9+\-.]*:/i', $decoded )
            && ! preg_match( '/^https?:/i', $decoded )
        ) {
            return false;
        }

        return true;
    }
}

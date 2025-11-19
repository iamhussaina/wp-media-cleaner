<?php
/**
 * Handles the logic for finding and deleting orphaned media attachments.
 * Includes admin interface rendering and action processing.
 *
 * @package Hussainas_Media_Cleaner
 * @version     1.0.0
 * @author      Hussain Ahmed Shrabon
 * @license     GPL-2.0-or-later
 * @link        https://github.com/iamhussaina
 * @textdomain  hussainas
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

class Hussainas_Cleaner_Engine {

    /**
     * Text domain for localization.
     *
     * @var string
     */
    private $text_domain = 'hussainas';

    /**
     * Page slug for the admin menu.
     *
     * @var string
     */
    private $page_slug = 'hussainas-orphan-cleaner';

    /**
     * Constructor.
     * Initializes hooks and actions.
     */
    public function __construct() {
        // Register the admin menu under the "Tools" section.
        add_action( 'admin_menu', [ $this, 'register_admin_menu' ] );

        // Handle form submission (deletion logic).
        add_action( 'admin_init', [ $this, 'handle_deletion_request' ] );
    }

    /**
     * Registers the admin page under the Tools menu.
     *
     * @return void
     */
    public function register_admin_menu() {
        add_management_page(
            __( 'Orphaned Media Cleaner', 'hussainas' ),
            __( 'Media Cleaner', 'hussainas' ),
            'manage_options', // Capability required
            $this->page_slug,
            [ $this, 'render_admin_view' ]
        );
    }

    /**
     * Handles the deletion logic when the form is submitted.
     * Validates nonces and permissions before execution.
     *
     * @return void
     */
    public function handle_deletion_request() {
        // Check if our specific delete action is triggered.
        if ( ! isset( $_POST['hussainas_action'] ) || 'delete_media' !== $_POST['hussainas_action'] ) {
            return;
        }

        // Verify Nonce for security (CSRF protection).
        if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'hussainas_delete_orphans' ) ) {
            wp_die( __( 'Security check failed. Please try again.', 'hussainas' ) );
        }

        // Check user capability.
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( __( 'You do not have permission to perform this action.', 'hussainas' ) );
        }

        // Retrieve selected media IDs.
        $media_ids = isset( $_POST['media_ids'] ) ? array_map( 'intval', $_POST['media_ids'] ) : [];

        if ( empty( $media_ids ) ) {
            add_settings_error(
                $this->page_slug,
                'no_selection',
                __( 'No media items selected for deletion.', 'hussainas' ),
                'error'
            );
            return;
        }

        $deleted_count = 0;

        // Loop through and delete permanently.
        foreach ( $media_ids as $id ) {
            // Force delete (true) skips trash and removes file from server.
            $result = wp_delete_attachment( $id, true );
            if ( $result ) {
                $deleted_count++;
            }
        }

        // Store a transient to show the success message on the next load/redirect.
        set_transient( 'hussainas_delete_count', $deleted_count, 30 );

        // Redirect back to the page to prevent form resubmission on refresh.
        wp_redirect( add_query_arg( 'page', $this->page_slug, admin_url( 'tools.php' ) ) );
        exit;
    }

    /**
     * Queries the database for orphaned attachments.
     * Definition: Post type 'attachment' with post_parent = 0.
     *
     * @param int $limit Number of items to fetch per page to manage memory.
     * @return WP_Query
     */
    private function get_orphaned_media( $limit = 50 ) {
        $args = [
            'post_type'      => 'attachment',
            'post_status'    => 'inherit',
            'post_parent'    => 0, // The core definition of unattached/orphaned in WP DB
            'posts_per_page' => $limit,
            'orderby'        => 'date',
            'order'          => 'DESC',
        ];

        return new WP_Query( $args );
    }

    /**
     * Renders the Admin HTML Interface.
     *
     * @return void
     */
    public function render_admin_view() {
        // Check for success messages from redirection.
        $deleted_count = get_transient( 'hussainas_delete_count' );
        if ( false !== $deleted_count ) {
            delete_transient( 'hussainas_delete_count' );
            echo '<div class="notice notice-success is-dismissible"><p>' . sprintf( __( '%d items successfully deleted.', 'hussainas' ), $deleted_count ) . '</p></div>';
        }

        // Fetch data.
        $orphans = $this->get_orphaned_media( 100 ); // Fetch up to 100 items at a time.
        ?>
        <div class="wrap">
            <h1 class="wp-heading-inline"><?php _e( 'Orphaned Media Cleaner', 'hussainas' ); ?></h1>
            <p class="description">
                <?php _e( 'Scan and remove media files that are not attached to any specific post or page (post_parent is 0).', 'hussainas' ); ?>
                <br>
                <strong><?php _e( 'Warning:', 'hussainas' ); ?></strong> <?php _e( 'Please verify files before deleting. Images uploaded directly to the library but used in page builders might appear here.', 'hussainas' ); ?>
            </p>

            <hr class="wp-header-end">

            <form method="post" action="">
                <?php wp_nonce_field( 'hussainas_delete_orphans' ); ?>
                <input type="hidden" name="hussainas_action" value="delete_media">

                <?php if ( $orphans->have_posts() ) : ?>
                    <div class="tablenav top">
                        <div class="alignleft actions">
                            <input type="submit" class="button button-primary" value="<?php esc_attr_e( 'Delete Selected Permanently', 'hussainas' ); ?>" onclick="return confirm('<?php _e( 'Are you sure? This cannot be undone.', 'hussainas' ); ?>');">
                        </div>
                    </div>

                    <table class="wp-list-table widefat fixed striped table-view-list media">
                        <thead>
                            <tr>
                                <td id="cb" class="manage-column column-cb check-column">
                                    <label class="screen-reader-text" for="cb-select-all-1"><?php _e( 'Select All', 'hussainas' ); ?></label>
                                    <input id="cb-select-all-1" type="checkbox">
                                </td>
                                <th scope="col" class="manage-column column-thumbnail"><?php _e( 'Thumbnail', 'hussainas' ); ?></th>
                                <th scope="col" class="manage-column column-title"><?php _e( 'Filename', 'hussainas' ); ?></th>
                                <th scope="col" class="manage-column column-date"><?php _e( 'Date Uploaded', 'hussainas' ); ?></th>
                                <th scope="col" class="manage-column column-type"><?php _e( 'Type', 'hussainas' ); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ( $orphans->have_posts() ) : $orphans->the_post(); 
                                $id = get_the_ID();
                                $filename = basename( get_attached_file( $id ) );
                            ?>
                                <tr>
                                    <th scope="row" class="check-column">
                                        <input type="checkbox" name="media_ids[]" value="<?php echo esc_attr( $id ); ?>">
                                    </th>
                                    <td class="column-thumbnail" style="width: 100px;">
                                        <?php 
                                        if ( wp_attachment_is_image( $id ) ) {
                                            echo wp_get_attachment_image( $id, [60, 60], true ); 
                                        } else {
                                            echo '<span class="dashicons dashicons-media-default" style="font-size:40px; height:40px; width:40px; color:#ccc;"></span>';
                                        }
                                        ?>
                                    </td>
                                    <td class="column-title">
                                        <strong><?php the_title(); ?></strong>
                                        <p class="description"><?php echo esc_html( $filename ); ?></p>
                                    </td>
                                    <td class="column-date"><?php echo get_the_date(); ?></td>
                                    <td class="column-type"><?php echo get_post_mime_type(); ?></td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                    
                    <div class="tablenav bottom">
                        <p class="description" style="margin-top: 10px;">
                            <?php _e( 'Showing latest 100 unattached items. Delete these to load more.', 'hussainas' ); ?>
                        </p>
                    </div>

                <?php else : ?>
                    <div class="notice notice-info inline">
                        <p><?php _e( 'Great! No orphaned media found.', 'hussainas' ); ?></p>
                    </div>
                <?php endif; wp_reset_postdata(); ?>
            </form>
        </div>
        <?php
    }
}

<?php

namespace Module;

use \Gloves\PostType;

class Event extends PostType {

    public static function init() {

        static::setup('calendar_event', 'event', 'events');
        static::addMetaBox('MetaBox');
        add_action('wp_ajax_update_event', array('\Module\Event', 'updateEvent'));
        add_action('wp_ajax_delete_event', array('\Module\Event', 'deleteEvent'));
    }

    public static function activate_once() {
        $event_id = static::insert('Example Event', 'Simple Description');
        \Gloves\Logger::write('Insert Event: '.$event_id);
        $start = new \DateTime(current_time('Y-m-d H:i'));
        $end = new \DateTime(current_time('Y-m-d H:i'));
        $end->modify('+2 hours');
        \update_post_meta($event_id, 'start', $start);
        \update_post_meta($event_id, 'end', $end);
        flush_rewrite_rules(true);
    }

    public static function activate() {
        ;
    }

    public static function deactivate() {
        $event = static::getBy('name', 'Example Event');
        wp_delete_post($event[0]->ID, true);
    }

    public static function uninstall() {
        $query = new WP_Query(array('post_type' => static::getInstance()->slug));
        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                wp_delete_object_term_relationships($query->post->ID, 'alt-calendar');
                wp_delete_post($query->post->ID);
            }
        }
    }

    /*
     * Deletes event from database
     * 
     * @return post_ID. Deleted posts id
     */

    public static function deleteEvent() {
        $post_id = intval($_POST['data']);
        echo $post_id;
        wp_delete_post($post_id);
        wp_die();
    }

    /*
     * AJAX update event in database
     * 
     * @param array. Event
     * @return int. PostID of updated or added event
     */

    public static function updateEvent() {
        global $wpdb;

        $event = $_POST["data"];
        $calendar_id = intval($_POST["calendar_id"]);
        $current_user = wp_get_current_user();
        $user_id = $current_user->ID;
        $response = [];

        $id = intval($event['id']);

        $title = sanitize_text_field($event['title']);
        $start = new \DateTime($event['start']);

        $end = new \DateTime($event['start']);

        $end->modify("+2 hours");

        if ($event['end'] != '') {
            $end = new \DateTime($event['end']);
        }

        if ($event['description'] != '') {
            $desc = sanitize_text_field($event['description']);
        }

        $calendar_event = array(
            'post_title' => $title,
            'post_status' => 'publish',
            'post_content' => $desc . ' ',
            'post_type' => 'calendar_event'
        );
        if (isset($event['post_id'])) {
            $post_id = $event['post_id'];
            $calendar_event['ID'] = $post_id;
        }
        $post_id = wp_insert_post($calendar_event);
        if (is_wp_error($post_id)) {
            $errors = $post_id->get_error_messages();
            foreach ($errors as $error) {
                echo $error;
            }
        }

        if ($post_id) {
            $response = $post_id;
            update_post_meta($post_id, 'start', $start);
            update_post_meta($post_id, 'end', $end);
            update_post_meta($post_id, 'event_id', esc_attr($id));
            update_post_meta($post_id, 'user_id', $user_id);
            wp_set_object_terms($post_id, intval($calendar_id), 'alt-calendar');
        }
        echo $response;

        wp_die();
    }

}

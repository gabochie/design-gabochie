<?php
defined('ABSPATH') || exit;

class Gabochie_Auto_Scheduler {
    private static $instance = null;

    public static function instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        add_action('gab_generate_content', array($this, 'execute_scheduled_generation'));
        add_action(Gab_Auto_Executor::HOOK, array(Gab_Auto_Executor::instance(), 'run_due'));
        add_filter('cron_schedules', array($this, 'add_cron_schedules'));
    }

    public function add_cron_schedules($schedules) {
        $schedules['gab_twicedaily'] = array(
            'interval' => 43200,
            'display' => 'Twice Daily',
        );
        $schedules['gab_every_other_day'] = array(
            'interval' => 172800,
            'display' => 'Every Other Day',
        );
        $schedules['gab_weekly'] = array(
            'interval' => 604800,
            'display' => 'Once Weekly',
        );
        return $schedules;
    }

    public function execute_scheduled_generation() {
        if (get_option('gab_enabled', '0') !== '1') return;
        if (get_option('gab_mode', 'bank') === 'bank') return; // bank mode owns the cadence.

        $generator = Gabochie_Auto_Generator::instance();
        $result = $generator->generate_post();

        if (is_wp_error($result)) {
            $errors = get_option('gab_errors', array());
            $errors[] = array(
                'time' => current_time('mysql'),
                'error' => $result->get_error_message(),
            );
            update_option('gab_errors', array_slice($errors, -20));
        } else {
            update_option('gab_last_generated', current_time('mysql'));
            $count = get_option('gab_total_generated', 0);
            update_option('gab_total_generated', $count + 1);
        }
    }

    public static function setup_schedule() {
        $frequency = get_option('gab_frequency', 'daily');
        $time = get_option('gab_time', '06:00');
        if (!preg_match('/^\d{1,2}:\d{2}$/', (string) $time)) {
            $time = '06:00';
        }
        list($hour, $minute) = explode(':', $time);
        $timestamp = strtotime("today {$hour}:{$minute}:00");
        if ($timestamp <= current_time('timestamp')) {
            $timestamp = strtotime("tomorrow {$hour}:{$minute}:00");
        }

        wp_clear_scheduled_hook('gab_generate_content');
        wp_clear_scheduled_hook(Gab_Auto_Executor::HOOK);
        if (get_option('gab_enabled', '0') === '1') {
            wp_schedule_event($timestamp, $frequency, 'gab_generate_content');
            wp_schedule_event($timestamp, 'daily', Gab_Auto_Executor::HOOK);
        }
    }
}

Gabochie_Auto_Scheduler::instance();

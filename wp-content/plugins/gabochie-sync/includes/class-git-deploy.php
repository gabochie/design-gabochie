<?php
if (!defined('ABSPATH')) exit;

class Gabochie_Git_Deploy {

    private $repo_path;
    private $remote;
    private $branch;

    public function __construct() {
        $this->repo_path = get_option('gabochie_sync_git_repo_path', ABSPATH);
        $this->remote = get_option('gabochie_sync_git_remote', 'origin');
        $this->branch = get_option('gabochie_sync_git_branch', 'main');
    }

    public function is_available() {
        $result = shell_exec('git --version 2>&1');
        return !empty($result) && strpos($result, 'git version') !== false;
    }

    public function get_status() {
        if (!$this->is_available()) {
            return array('available' => false, 'message' => 'Git is not installed on this server.');
        }

        $is_repo = $this->run_command('git rev-parse --is-inside-work-tree');
        if ($is_repo !== 'true') {
            return array('available' => true, 'is_repo' => false, 'message' => 'Not a Git repository.');
        }

        $branch = $this->run_command('git branch --show-current');
        $status = $this->run_command('git status --porcelain');
        $log = $this->run_command('git log --oneline -5');
        $remote_url = $this->run_command('git remote get-url ' . escapeshellarg($this->remote));

        return array(
            'available' => true,
            'is_repo' => true,
            'branch' => trim($branch),
            'remote_url' => trim($remote_url),
            'has_changes' => !empty(trim($status)),
            'uncommitted' => substr_count(trim($status), "\n") + 1,
            'recent_commits' => trim($log),
        );
    }

    public function pull_remote() {
        if (!$this->is_available()) {
            return array('success' => false, 'message' => 'Git is not installed.');
        }

        // Stash any local changes
        $stash = $this->run_command('git stash push -m ' . escapeshellarg('sync-pull-' . time()));
        $stashed = (strpos($stash, 'No local changes') === false);

        // Pull
        $output = $this->run_command('git pull ' . escapeshellarg($this->remote) . ' ' . escapeshellarg($this->branch) . ' 2>&1', true);

        // Restore stash if we stashed
        if ($stashed) {
            $this->run_command('git stash pop');
        }

        $success = strpos($output, 'Already up to date') !== false ||
                    strpos($output, 'Successfully pulled') !== false ||
                    strpos($output, 'Updating') !== false;

        $this->log_deploy('pull', $success ? 'success' : 'error', $output);

        return array(
            'success' => $success,
            'message' => $success ? 'Pulled latest from ' . $this->remote . '/' . $this->branch : 'Pull failed.',
            'output' => $output,
        );
    }

    public function push_local($commit_message = '') {
        if (!$this->is_available()) {
            return array('success' => false, 'message' => 'Git is not installed.');
        }

        if (empty($commit_message)) {
            $commit_message = 'Sync push from local — ' . current_time('mysql');
        }

        $this->run_command('git add -A');
        $commit = $this->run_command('git commit -m ' . escapeshellarg($commit_message));
        $push = $this->run_command('git push ' . escapeshellarg($this->remote) . ' ' . escapeshellarg($this->branch) . ' 2>&1', true);

        $success = strpos($push, '->') !== false || strpos($push, 'Everything up-to-date') !== false;

        $this->log_deploy('push', $success ? 'success' : 'error', $push);

        return array(
            'success' => $success,
            'message' => $success ? 'Pushed to ' . $this->remote . '/' . $this->branch : 'Push failed.',
            'output' => $push,
            'commit' => trim($commit),
        );
    }

    public function get_commits($count = 10) {
        $log = $this->run_command('git log --oneline -' . intval($count));
        return array(
            'success' => true,
            'commits' => trim($log),
        );
    }

    private function run_command($cmd, $return_all = false) {
        $cwd = getcwd();
        if ($this->repo_path && is_dir($this->repo_path)) {
            chdir($this->repo_path);
        }
        $output = shell_exec($cmd . ' 2>&1');
        chdir($cwd);
        return $return_all ? $output : trim($output);
    }

    private function log_deploy($action, $status, $output) {
        $log = get_option('gabochie_sync_log', array());
        $log[] = array(
            'time' => current_time('mysql'),
            'action' => 'git_' . $action,
            'status' => $status,
            'message' => $output,
        );
        if (count($log) > 200) {
            $log = array_slice($log, -200);
        }
        update_option('gabochie_sync_log', $log);
    }
}

<?php

namespace Eckinox\Library\General;

class Git {

    public static function getCommit() {
        return trim(`git log --pretty="%h" -n1 HEAD`);
    }

    public static function getBranch() {
        return trim(`git branch | grep \*`, "*\ \n");
    }

    public static function getOrigin($full_list = false) {
        return trim(! $full_list ? `git config --get remote.origin.url` : `git remote show origin`);
    }

    public static function getRemoteName() {
        $origin = static::getOrigin();

        # We have an SSH connection as default remote
        if ( strpos($origin, "http://") === false ) {
            return substr($origin, strpos($origin, ':') + 1, -strlen('.git'));
        }
        else {
            # return parse_url($origin,  PHP_URL_PATH);
        }

        return false;
    }


    public static function getCommitDate($format = "%F") {
        $commit = static::getCommit();
        $time = trim(`git show -s --format=%ct $commit`);

        return strftime($format, $time);
    }

    public static function getUpdateCount($origin = null, $branch = null) {
        $origin ?? $origin = 'origin';
        $branch ?? $branch = static::getBranch();

        return trim(`git rev-list HEAD...$origin/$branch --count`);
    }

    public static function getFileChanged() {
        return trim(`git status --porcelain`);
    }

    public static function pull($origin = null, $branch = null) {
        $origin ?? $origin = 'origin';
        $branch ?? $branch = static::getBranch();

        return trim(`git pull $origin $branch`);
    }
}

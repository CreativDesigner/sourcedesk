<?php
// Handler for minimum age

class MinAge
{
    protected $verifiedAge = 0;

    public function __construct()
    {
        $this->verifiedAge = $this->parse($_SESSION['minage'] ?? 0);
    }

    public function req($age = null, $target = "")
    {
        global $CFG, $realPage, $allowedPages;

        $ex = explode("/", $realPage);
        $realPage2 = array_shift($ex);

        if (in_array($realPage, $allowedPages) || in_array($realPage2, $allowedPages)) {
            return;
        }

        if ($age === null) {
            $age = $CFG['MIN_AGE'] ?? 0;
        }

        $age = $this->parse($age);

        if ($age > $this->verifiedAge) {
            $this->verify($age, $target);
        }
    }

    public function product($id)
    {
        global $db, $CFG;

        $sql = $db->query("SELECT min_age FROM products WHERE ID = " . intval($id));
        if ($sql->num_rows) {
            $this->req($sql->fetch_object()->min_age);
        }
    }

    public function cms($slug, $target = "")
    {
        global $db, $CFG;

        $sql = $db->query("SELECT min_age FROM cms_pages WHERE slug LIKE '" . $db->real_escape_string($slug) . "'");
        if ($sql->num_rows) {
            $this->req($sql->fetch_object()->min_age, $target);
        }
    }

    protected function verify($age, $target = "")
    {
        global $CFG, $lang;

        if (empty($target)) {
            $target = $CFG['PAGEURL'];
        }

        $msg = $lang['GLOBAL']['MINAGE'];
        $msg = str_replace("%a", $age, $msg);

        ?>
        <style>
        body {
            display: none;
        }
        </style>

        <script>
        if (confirm("<?=$msg;?>")) {
            function showBody() {
                document.body.style.display = "block";
            }

            document.addEventListener('DOMContentLoaded', showBody, false);
            window.addEventListener('load', showBody, false);

            var xhr = new XMLHttpRequest;
            xhr.open("POST", "<?=$CFG['PAGEURL'];?>minage", true);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhr.send("csrf_token=<?=CSRF::raw();?>&age=<?=$age;?>");
        } else {
            window.location = "<?=$target;?>";
        }
        </script>
        <?php
}

    protected function parse($age)
    {
        return max(0, intval($age));
    }
}
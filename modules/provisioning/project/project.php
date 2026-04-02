<?php

class ProjectProv extends Provisioning
{
    protected $name = "Projekt";
    protected $short = "project";
    protected $lang;
    protected $options = array();

    public function Config($id, $product = true)
    {
        global $db, $CFG;
        $this->loadOptions($id, $product);

        $templatesSql = $db->query("SELECT ID, name FROM project_templates");

        ob_start();?>

		<input style="opacity: 0;position: absolute;">
        <input type="password" autocomplete="new-password" style="display: none;">

		<div class="row">
			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("project");?></label>
					<input type="text" data-setting="project" value="<?=htmlentities($this->getOption("project"));?>" placeholder="<?=$this->getLang("project");?>" class="form-control prov_settings" />
				</div>
			</div>

			<div class="col-md-6">
				<div class="form-group">
					<label><?=$this->getLang("template");?></label>
					<select data-setting="template" class="form-control prov_settings">
                        <option value="0"><?=$this->getLang("empty"); ?></option>
                        <?php while ($r = $templatesSql->fetch_object()) {?>
                        <option value="<?=$r->ID;?>" <?php if ($this->getOption("template") == $r->ID) {
                            echo "selected=\"selected\"";
                        }
                            ?>><?=htmlentities($r->name);?></option>
                        <?php }?>
                    </select>
				</div>
			</div>
		</div>

		<?php
$res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function Create($id)
    {
        global $db, $CFG;
        $this->loadOptions($id);
        $user = $this->getClient($id);

        $name = $db->real_escape_string(trim($this->getOption("project")));
        $db->query("INSERT INTO projects (`user`, `due`, `name`, `entgelt`) VALUES (" . $user->get()['ID'] . ", '" . date("Y-m-d") . "', '" . $name . "', 0)");

        $projectId = $db->insert_id;

        if ($this->getOption("template")) {
            $tsql = $db->query("SELECT * FROM project_templates WHERE ID = '" . intval($this->getOption("template"))  . "' LIMIT 1");
            if ($tsql->num_rows) {
                $tinfo = $tsql->fetch_object();

                $tasks = unserialize($tinfo->tasks);
                foreach ($tasks as $k => $v) {
                    $db->query("INSERT INTO project_tasks (`project`, `name`, `description`) VALUES ('" . $projectId . "', '" . $db->real_escape_string($k) . "', '" . $db->real_escape_string($v) . "')");
                }

            }
        }

        return array(true, array(
            "pid" => $projectId,
        ));
    }

    public function Output($id, $task = "")
    {
        global $pars, $sec;
        $this->loadOptions($id);

        ob_start();

        ?>
		<div class="panel panel-default">
			<div class="panel-heading"><?=$this->getLang("THANKS");?></div>
			<div class="panel-body">
            <?=$this->getLang("THANKS2");?>
			</div>
		</div>
		<?php

        $res = ob_get_contents();
        ob_end_clean();
        return $res;
    }

    public function AllEmailVariables()
    {
        return array();
    }

    public function EmailVariables($id)
    {
        return array();
    }
}
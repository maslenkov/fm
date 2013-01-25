<?php 
/*
 * file maneger version 0.1.0.0
 * http://tests/index.php?r=fileManager&dir=/
 */
?>
<h2>File manager</h2>

<table>
    <thead>
        <th>Name</th>
        <th>Last update</th>
        <th>Size(kb)</th>
    </thead>
    <tbody>
        <?php foreach (FMFosad::model(isset($_REQUEST['dir'])?$_REQUEST['dir']:'/')->run() as $item) : ?>
            <tr>
                <td><?= FMHelper::link($item) ?></td>
                <td><?= $item->last_update ?></td>
                <td><?= $item->size ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<?php
// http://tests/index.php?r=fileManager&dir=/

class FMFosad extends FM {
    private static $_instance;
    private $_dir;
    
    static public function model ($dir = '/') {
        if (!self::$_instance) {
            self::$_instance = new FMFosad($dir);
        }
        return self::$_instance;
    }
    
    private function __construct ($dir) {
        if (!is_dir($dir))
            throw new Exception("Директории \"{$dir}\" не существует");
        if (!$this->_checkAccess($dir))
            throw new Exception("Недостаточно прав для перехода к директории \"{$dir}\"");
        $this->_dir = $dir;
    }

    public function run () {
        $items = array();
        if ($this->_dir !== '/') {
            $this->_dir .= '/';
            $_ = pathinfo($this->_dir);
            $_ = new FMItem($_['dirname']);
            $_->name = '../';
            $items[] = $_;
        }
        $dir = opendir($this->_dir);
        while($fileName = readdir($dir)){
            if($fileName !== '.' && $fileName !== '..'){
                $path = $this->_dir.$fileName;
                $items[] = new FMItem($path);
            }
        }
        closedir($dir);
        return $items;
    }
}

class FMItem extends FM {
    public  $path,
            $name,
            $is_dir,
            $size,
            $last_update,
            $access;
    
    public function __construct($path) {
        $this->path = $path;
        $this->name = basename($path);
        if (is_dir($path)){
            $this->is_dir = 1;
        } elseif (is_file($path)) {
            $this->is_dir = 0;
            $this->size = ($_ = filesize($path)) ? number_format($_ / 1024, 2) : 0;
        }
        $this->last_update = date("F d Y H:i:s", filemtime($path));
        $this->access = $this->_checkAccess($path);
    }
    
    public function __toString() {
        return "name: {$this->name}\tpath: {$this->path}\tis_dir: {$this->is_dir}\tsize: {$this->size}\tlast_update: {$this->last_update}\taccess: " .
            $this->access;
    }
}

class FM {
    protected function _checkAccess($path) {
        return intval(substr(sprintf('%o', fileperms($path)), -2, 1) >= 5);
    }
}

class FMHelper {
    static public function link ($fmitem) {
        echo ($fmitem->is_dir ? 'd' : 'f') . ' ';
        if($fmitem->is_dir && $fmitem->access) {
            echo "<a href=\"?r=fileManager&dir={$fmitem->path}\">{$fmitem->name}</a>";
        } else {
            echo $fmitem->name;
        }
    }
}

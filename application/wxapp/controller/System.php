<?php
namespace app\wxapp\controller;
use app\wxapp\controller\Base;
use think\Db;
class System extends Base {

   public function download(){
     	$system = Db::name('system')->where('id',1)->field('andown,iosdown')->find();
     	//var_dump($system);exit;
     	$this->assign('down',$system);;
   		 return $this->fetch();
   }
}


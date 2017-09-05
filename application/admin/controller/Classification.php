<?php
namespace app\admin\controller;
use \think\Db;
use app\admin\controller\Admincommon;
use think\Request;
class Classification extends Admincommon
{
    public $arctype=array();
    public function __construct(Request $request)
    {
        parent::__construct($request);
        $this->checkLogin($request);
    }

    public function index()
    {
        //获取分类数据 并组织数据
        $data=$this->getArctype();
        $this->assign('arctype',json_encode($this->arctype+[0=>'顶级目录']));
        $this->assign('data',json_encode($data));
        return $this->fetch();
    }

    public function add(){
        $data=$this->getArctype(0,false);
        $this->assign('arctype',$this->arctype+[0=>'顶级目录']);
        $this->assign('data',json_encode($data));
        return $this->fetch();
    }

    public function edit(){
        $id=input('get.id');
        $info=Db::name('classification')->find($id);
        $data=$this->getArctype(0,false);
        $this->assign('arctype',$this->arctype+[0=>'顶级目录']);
        $this->assign('data',json_encode($data));
        $this->assign('info',$info);
        return $this->fetch();
    }

    /**
     * 添加分类
     */
    public function doadd(){
        $paramet=[
            'name'=>input('post.name'),
            'pid'=>input('post.pid'),
            'sorting'=>input('post.sorting'),
            'description'=>input('post.description')
        ];
        $res=Db::name('classification')->insert($paramet);
        if($res)
            echo json_encode(['success'=>true]);
        else
            echo json_encode(['success'=>false]);
    }

    /**
     * 编辑分类
     */
    public function doedit(){
        $id=input('post.id');
        $paramet=[
            'name'=>input('post.name'),
            'pid'=>input('post.pid'),
            'sorting'=>input('post.sorting'),
            'description'=>input('post.description')
        ];
        $res=Db::name('classification')->where('id',$id)->update($paramet);
        if($res)
            echo json_encode(['success'=>true]);
        else
            echo json_encode(['success'=>false]);
    }

    /**
     * 获取分类数据
     * @param int $pid
     * @param bool $spread      //是否展开
     * @return array|false|\PDOStatement|string|\think\Collection
     */
    public function getArctype($pid=0,$spread=true){
        $data=Db::name('classification')->where(['pid'=>$pid])->order('sorting','ASC')->select();
        if(!empty($data)){
            foreach($data as $key=>$value){
                $this->arctype[$value['id']]=$value['name'];
//                $data[$key]['name']=$value['name']."&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;----------".$value['sorting'];
                $tmp=$this->getArctype($value['id']);
                if(!empty($tmp)){
                    $data[$key]['spread']=$spread;
                    $data[$key]['children']=$tmp;
                }
            }
        }

        return empty($data)?array():$data;
    }
}

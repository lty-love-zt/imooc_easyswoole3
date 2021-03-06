<?php
namespace App\HttpController\Api;
use EasySwoole\Http\AbstractInterface\Controller;
use EasySwoole\Validate\Validate;
use EasySwoole\Http\Message\Status;
/**
 * Api模块下的基础类库
 * Class Base
 * @package App\HttpController\Api
 */
class Base extends Controller
{
    /**
     * 存放请求参数数据
     * @var array
     */
    public $params = [];

    public function index()
    {

    }

    /**
     * 类似于拦截器
     * @param string|null $action
     * @return bool|null
     */
    protected function onRequest(?string $action): ?bool
    {
        //获取请求参数
        $this->getParams();
        $ret =  parent::onRequest($action);
        if($ret === false){
            return false;
        }
        $v = $this->validateRule($action);
        if($v){
            $ret = $this->validate($v);
            if($ret == false){
                $this->writeJson(Status::CODE_BAD_REQUEST,"{$v->getError()->getField()}@{$v->getError()->getFieldAlias()}:{$v->getError()->getErrorRuleMsg()}", null);
                return false;
            }
        }
        return true;
    }

    /**
     * 验证规则
     * @param string|null $action
     * @return Validate|null
     */
    protected function validateRule(?string $action):?Validate
    {
        $v = new Validate();
        switch ($action){
            case 'add':{
                $v->addColumn('name','视频名称')->required('不能为空')->lengthMax(20,'长度错误');
                $v->addColumn('url','视频地址')->required('不能为空');
                $v->addColumn('image', '图片地址')->required('不能为空');
                $v->addColumn('content', '视频描述')->required('不能为空');
                $v->addColumn('cat_id', '栏目ID')->required('不能为空');
                break;
            }
        }
        return $v;
    }

    /**
     * 公共方法--获得请求参数
     */
    public function getParams()
    {
        $params = $this->request()->getRequestParam();
        $params['page'] = !empty($params['page']) ? intval($params['page']) : \Yaconf::get("page.page"); //可以将page和size的默认值写入到配置文件
        $params['size'] = !empty($params['size']) ? intval($params['size']) : \Yaconf::get("page.size");
        $params['from'] = ($params['page'] - 1) * $params['size'];
        $this->params = $params;
    }

    /**
     * 处理请求不合法的情况：找不到类
     * @param \Throwable $throwable
     * @param $actionName
     */
//    public function onException(\Throwable $throwable, $actionName): void
//    {
//        $this->writeJson(400, '请求不合法', []);
//    }

    /**
     * 重写父类中的writeJson方法
     * @param int $statusCode
     * @param null $message
     * @param null $result
     * @return bool
     */
    protected function writeJson($statusCode = 200,$message = null,$result = null){
        if(!$this->response()->isEndResponse()){
            $data = Array(
                "code"=>$statusCode,
                "message"=>$message,
                "result"=>$result,
            );
            $this->response()->write(json_encode($data,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES));
            $this->response()->withHeader('Content-type','application/json;charset=utf-8');
            $this->response()->withStatus($statusCode);
            return true;
        }else{
            trigger_error("response has end");
            return false;
        }
    }

    /**
     * php 分页处理
     * @param $count
     * @param $data
     * @param int $isSplice
     * @return array
     */
    public function getPagingDatas($count, $data, $isSplice = 1)
    {
        $totalPage = ceil($count / $this->params['size']);

        $maxPageSize = \Yaconf::get("page.maxPageSize");
        if ($totalPage > $maxPageSize) {
            $totalPage = $maxPageSize; //避免深度分页
        }

        $data = $data ?? [];
        if ($isSplice ==1) {
            $data = array_splice($data, $this->params['from'], $this->params['size']);
        }
        return [
            'total_page' => $totalPage,
            'page_size' => intval($this->params['size']),
            'count' => $count,
            'lists' => $data
        ];
    }


}

<?php
namespace App\Model\Es;

use EasySwoole\Component\Singleton;
use Elasticsearch\ClientBuilder;
use EasySwoole\EasySwoole\Logger;
class EsClient
{
    //单例模式
    use Singleton;

    public  $esClinet = null;

    //私有化构造函数
    private function __construct()
    {
        $config = \Yaconf::get("es");
        try {
            //es实例
            $this->esClinet = ClientBuilder::create()->setHosts([$config['host'] . ":" . $config['port']])->build();
        } catch (\Exception $e) {
            //记录日志
            Logger::getInstance()->log($e->getMessage());
        }

        if (empty($this->esClinet)) {
            throw new \Exception("es连接异常");
        }

    }

    //私有化克隆方法
    private function __clone(){ }


    /**
     *
     * @param $name
     * @param $arguments
     * @return mixed
     */
    public function __call($name, $arguments)
    {
        return $this->esClinet->$name(...$arguments);
    }
}

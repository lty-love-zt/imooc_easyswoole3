<?php
namespace EasySwoole\EasySwoole;


use EasySwoole\EasySwoole\Swoole\EventRegister;
use EasySwoole\EasySwoole\AbstractInterface\Event;
use EasySwoole\Http\Request;
use EasySwoole\Http\Response;
//use EasySwoole\Core\Swoole\Process\ProcessManager;
use EasySwoole\EasySwoole\ServerManager;
use \EasySwoole\Component\Di;
use App\Lib\Redis\Redis;
use App\Lib\Redis\Redis2;
use EasySwoole\Pool\Tests\Pool;
use EasySwoole\Utility\File;
use App\Lib\Process\ConsumerTest;
//use EasySwoole\Component\Crontab\CronTab;
use App\Lib\Cache\Video as videoCache;
//Timer定时器
use EasySwoole\Component\Timer;
//elasticsearch客户端
use App\Model\Es\EsClient;
//静态化API
use EasySwoole\FastCache\Cache;
//mysql连接池
use App\Lib\Pool\MysqlPool;
use EasySwoole\Component\Pool\PoolManager;
//redis连接池
use App\Lib\Pool\RedisPool;

class EasySwooleEvent implements Event
{

    public static function initialize()
    {
        // TODO: Implement initialize() method.
        date_default_timezone_set('Asia/Shanghai');

        //注册mysql数据库连接池
        $mysqlConfig = \Yaconf::get('mysql');
        print_r($mysqlConfig);
        PoolManager::getInstance()->register(MysqlPool::class,8);

        //注册redis连接池
        $redisConfig = \Yaconf::get('redis');
        PoolManager::getInstance()->register(RedisPool::class,$redisConfig['POOL_MAX_NUM']);
    }

    public static function mainServerCreate(EventRegister $register)
    {
        // TODO: Implement mainServerCreate() method.
        //mysql 相关
        Di::getInstance()->set('MYSQL', \MysqliDb::class, Array(
            'host' => \Yaconf::get("database.host"),
            'username' => \Yaconf::get("database.username"),
            'password' => \Yaconf::get("database.password"),
            'db' => \Yaconf::get("database.db"),
            'port' => \Yaconf::get("database.port"),
            'charset' => \Yaconf::get("database.charset")
        ));

        //redis相关
        Di::getInstance()->set('REDIS', Redis2::getInstance());

        //Elasticsearch容器注入
        Di::getInstance()->set('ES', EsClient::getInstance());

        //注册fast-cache服务
        Cache::getInstance()->setTempDir(EASYSWOOLE_TEMP_DIR)->attachToServer(ServerManager::getInstance()->getSwooleServer());

        //注册消费者进程
//        $allNum = 3;
//        for ($i = 0; $i < $allNum; $i++) {
//            ProcessManager::getInstance()->addProcess("consumer_{$i}", ConsumerTest::class);
//        }

        //easyswoole3.x写法
//        $allNum = 3;
//        for ($i = 0; $i < $allNum; $i++) {
//            ServerManager::getInstance()->getSwooleServer()->addProcess((new ConsumerTest("imooc_customer_testp_{$i}"))->getProcess());
//        }

        //easyswoole+crontab 定时任务的用法
        $cacheVideoObj = new videoCache();
//        Crontab::getInstance()
//            ->addRule("test_singwa_crontab", "*/1 * * * *",
//                function () use($cacheVideoObj){ //闭包函数使用外部的变量
//                    $cacheVideoObj->setIndexVideo();
//            })
//            ->addRule("test_singwa_crontab2", "*/2 * * * *",
//                function () {
//                    var_dump("singwa_crontab2");
//            })
//        ;

        //easyswoole基于swoole的定时任务的用法
//        Timer::loop(1000*2, function () { //easyswoole自身的定时任务不能这么直接用
//            var_dump(1);
//        });
        $register->add(EventRegister::onWorkerStart, function (\swoole_server $server, $workerId) use ($cacheVideoObj){
            if ($workerId == 0) {
                //easyswoole2.x写法
//                Timer::loop(1000*2, function () use ($cacheVideoObj) { //easyswoole自身的定时任务的正确用法
//                    $cacheVideoObj->setIndexVideo();
//                });

                //easyswoole3.x写法
                Timer::getInstance()->loop(1000*2, function () use ($cacheVideoObj) { //easyswoole自身的定时任务的正确用法
                    $cacheVideoObj->setIndexVideo();
                });
            }
        });
    }

    public static function onRequest(Request $request, Response $response): bool
    {
        // TODO: Implement onRequest() method.
        return true;
    }

    public static function afterRequest(Request $request, Response $response): void
    {
        // TODO: Implement afterAction() method.
    }
}
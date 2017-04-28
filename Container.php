<?php

/**
 * Created by PhpStorm.
 * User: wangze
 * Date: 2017/4/28
 * Time: 16:06
 */
class Container
{
    //绑定
    protected $bindings = [];
    //绑定(抽象,实在的,公用的)
    public function bind($abstract,$concrete = null,$shared = false)
    {   //Closure 关闭
        if(!$concrete instanceof Closure){

            //如果提供的参数不是回掉函数，则产生默认的回掉函数
            $concrete = $this->getClousure($abstract,$concrete);
        }
        $this->bindings[$abstract] = compact('concrete','shared');
    }
    //默认生成实力的回掉函数
    protected function getClousure($abstart,$concrete)
    {
        return function ($c) use ($abstart,$concrete)
        {
            $method = ($abstart == $concrete) ? 'build' : 'make';
            return $c->$method($concrete);
        };
    }
    //生成实例对象
    public function make($abstart)
    {
        $concrete = $this->getClousure($abstart);

        if($this->isBuildable($abstart,$concrete)){
            $object = $this->bind($concrete);
        }else{
            $object = $this->make($concrete);
        }
    }
    protected function isBuildable($abstart,$concrete)
    {
        return $concrete === $abstart || $concrete instanceof Closure;
    }
    //获取绑定的回调函数
    protected function getConcrete($abstart)
    {
        if(!isset($this->bindings[$abstart])){
            return $abstart;
        }
        return $this->bindings[$abstart]['concrete'];
    }
    //实例化对象
    public function build($concrete)
    {
        if($concrete instanceof Closure){
            return $concrete($this);
        }
        $reflector = new ReflectionClass($concrete);
        if(!$reflector->isInstantiable()){
            echo $message = "Target [$concrete] is not instantiable";
        }
        $constructor = $reflector->getConstructor();
        if(is_null($constructor)){
            return new $concrete;
        }
        $dependencies = $constructor->getParameters();
        $instances = $this->getDependencies($dependencies);
        return $reflector->newInstanceArgs($instances);
    }

    protected function getDependencies($parameters)
    {
        $dependencies = [];
        foreach ($parameters as $parameter)
        {
            $dependency = $parameter->getClass();
            if(is_null($dependency)){
                $dependencies[] = null;
            }else{
                $dependencies[] = $this->resolveClass($parameter);
            }
        }
        return (array)$dependencies;
    }
    protected function resolveClass(ReflectionParameter $parameter)
    {
        return $this->make($parameter->getClass()->name);
    }
}
class Traveller
{
    protected $trafficTool;
    public function __construct(Visit $trafficTool)
    {
        $this->trafficTool = $trafficTool;
    }
    public function visitTiabet()
    {
        $this->trafficTool->go();
    }
}

//实例化容器
$app = new Container();

//完成容器的填充
$app->bind("VIsit","Train");
var_dump($app);die;
$app->bind("traveller","Traveller");
$tra = $app->make("traveller");
$tra->visitTiabet();
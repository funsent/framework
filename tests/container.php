<?php

//namespace funsent;
//
//use Closure;
//use ReflectionClass;
//use ReflectionException;
//use ReflectionParameter;
//use Exception;

// 实现一个简单的IOC容器

class Container
{
    
    protected $bindings = [];
    
    public function bind($abstract, $concrete = null, $shared = false)
    {
        if (! $concrete instanceof Closure) {
            $concrete = $this->getClosure($abstract, $concrete);
        }
        $this->bindings[$abstract] = compact('concrete', 'shared');
    }

    protected function getClosure($abstract, $concrete)
    {
        return function($container) use ($abstract, $concrete)
        {
            $method = ($abstract == $concrete) ? 'build' : 'make';
            return $container->$method($concrete);
        };
    }

    public function make($abstract)
    {
        $concrete = $this->getConcrete($abstract);
        if ($this->isBuildable($concrete, $abstract)) {
            $object = $this->build($concrete);
        } else {
            $object = $this->make($concrete);
        }
        return $object;
    }

    protected function getConcrete($abstract)
    {
        if (! isset($this->bindings[$abstract])) {
            return $abstract;
        }
        return $this->bindings[$abstract]['concrete'];
    }

    protected function isBuildable($concrete, $abstract)
    {
        return $concrete === $abstract || $concrete instanceof Closure;
    }

    public function build($concrete)
    {
        if ($concrete instanceof Closure) {
            return $concrete($this);
        }
        $reflector = new ReflectionClass($concrete);
        if (! $reflector->isInstantiable()) {
            echo $message = "Target [{$concrete}] is not instantiable.";
        }
        $constructor = $reflector->getConstructor();
        if (is_null($constructor)) {
            return new $concrete;
        }
        $dependencies = $constructor->getParameters();
        $instances = $this->getDependencies($dependencies);
        return $reflector->newInstanceArgs($instances);
    }

    protected function getDependencies($parameters)
    {
        $dependencies = [];
        foreach ($parameters as $parameter) {
            $dependency = $parameter->getClass();
            if (is_null($dependency)) {
                $dependencies[] = null;
            } else {
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

interface Visit
{
    public function go();
}

class Leg implements Visit
{
    public function go()
    {
        echo 'walt to Tibet!';
    }
}

class Car implements Visit
{
    public function go()
    {
        echo 'driver Car to Tibet!';
    }
}

class Train implements Visit
{
    public function go()
    {
        echo 'go to Tibet by train!';
    }
}

class Traveller
{
    protected $trafficTool;
    public function __construct(Visit $trafficTool)
    {
        $this->trafficTool = $trafficTool;
    }
    public function visitTibet()
    {
        $this->trafficTool->go();
    }
}

$app = new Container();
$app->bind('Visit', 'Train');
$app->bind('traveller', 'Traveller');
$traveller = $app->make('traveller');
$traveller->visitTibet();
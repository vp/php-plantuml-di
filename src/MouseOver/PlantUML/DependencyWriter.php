<?php

namespace MouseOver\PlantUML;

use Flagbit\Plantuml\TokenReflection\WriterAbstract;


/**
 * Class DependencyWriter
 * @package MouseOver\PlantUML
 */
class DependencyWriter extends WriterAbstract
{

    public function __construct()
    {
        $this->setIndent('');
    }

    /**
     * @param \TokenReflection\IReflectionClass $class
     * @param array $dependencies
     *
     * @return string
     */
    public function writeDependencies($class, array $dependencies)
    {
        $s = '';
        foreach ($dependencies as $depName => $depClass) {
            $s .= $this->formatLine(
                $this->writeObjectType($class) . ' ' . $this->formatClassName($class->getName()) . ' *-- '
                . $this->formatClassName(
                    $depClass
                )
            );
        }
        return $s;
    }

    /**
     * @param array $dependencies
     * @return string
     */
    public function writeProperties(array $dependencies)
    {
        $s = '';
        foreach ($dependencies as $depName => $depClass) {
            $s .= $this->formatLine($this->formatClassName($depClass));
        }
        return $s;
    }

    /**
     * @param \TokenReflection\IReflectionClass $class
     * @return string
     */
    private function writeObjectType(\TokenReflection\IReflectionClass $class)
    {
        $return = 'class';
        if (true === $class->isInterface()) {
            $return = 'interface';
        }
        return $return;
    }
}
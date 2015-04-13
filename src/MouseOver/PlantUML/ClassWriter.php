<?php

namespace MouseOver\PlantUML;

use Flagbit\Plantuml\TokenReflection\WriterAbstract;
use TokenReflection\IReflectionClass;
use TokenReflection\Php\ReflectionMethod;

class ClassWriter extends WriterAbstract
{

    /**
     * @var \Flagbit\Plantuml\TokenReflection\ConstantWriter
     */
    private $constantWriter;

    /**
     * @var \Flagbit\Plantuml\TokenReflection\PropertyWriter
     */
    private $propertyWriter;

    /**
     * @var \MouseOver\PlantUML\DependencyWriter
     */
    private $dependencyWriter;

    /**
     * @var \Flagbit\Plantuml\TokenReflection\MethodWriter
     */
    private $methodWriter;

    public function __construct()
    {
        $this->setIndent('');
    }

    /**
     * @param \Flagbit\Plantuml\TokenReflection\ConstantWriter $constantWriter
     */
    public function setConstantWriter($constantWriter)
    {
        $this->constantWriter = $constantWriter;
    }

    /**
     * @param \Flagbit\Plantuml\TokenReflection\MethodWriter $methodWriter
     */
    public function setMethodWriter($methodWriter)
    {
        $this->methodWriter = $methodWriter;
    }

    /**
     * @param \Flagbit\Plantuml\TokenReflection\PropertyWriter $propertyWriter
     */
    public function setPropertyWriter($propertyWriter)
    {
        $this->propertyWriter = $propertyWriter;
    }

    /**
     * @param \MouseOver\PlantUML\DependencyWriter $dependencyWriter
     */
    public function setDependencyWriter($dependencyWriter)
    {
        $this->dependencyWriter = $dependencyWriter;
    }

    /**
     * @param \TokenReflection\IReflectionClass $class
     *
     * @return string
     */
    public function writeElement(IReflectionClass $class)
    {
        $classString = $this->formatLine(
            $this->writeAbstract($class) . $this->writeObjectType($class) . ' ' . $this->formatClassName(
                $class->getName()
            ) . ' {'
        );

        if ($this->constantWriter) {
            $constantReflections = $class->getOwnConstantReflections();
            foreach ($class->getConstantReflections() as $otherConstantReflection) {
                /* @var $otherConstantReflection \TokenReflection\ReflectionConstant */
                $otherConstantName = $otherConstantReflection->getName();

                foreach ($constantReflections as $constantReflection) {
                    if ($constantReflection->getName() === $otherConstantName) {
                        // skip constants already defined in our current class
                        continue 2;
                    }
                }

                $constantReflections[] = $otherConstantReflection;
            }

            $classString .= $this->constantWriter->writeElements($constantReflections);
        }

        if ($this->propertyWriter) {
            $classString .= $this->propertyWriter->writeElements($class->getOwnProperties());
        }

        if ($this->methodWriter) {
            if ($this->dependencyWriter) {
                //- if dependency writer defined exclude constructor
                $ownMethods = $class->getOwnMethods(ReflectionMethod::IS_PUBLIC);
                $ownMethodsWithoutConstructor = [];
                foreach ($ownMethods as $method) {
                    /** @var ReflectionMethod $method */
                    if (!$method->isConstructor()) {
                        $ownMethodsWithoutConstructor[] = $method;
                    }
                }
                $classString .= $this->methodWriter->writeElements($ownMethodsWithoutConstructor);
            } else {
                $classString .= $this->methodWriter->writeElements($class->getOwnMethods());
            }
        }

        $dependencies = [];
        if ($this->dependencyWriter) {
            $constructor = $class->getConstructor();
            if ($constructor) {
                foreach ($constructor->getParameters() as $parameter) {
                    /** @var $parameter \TokenReflection\IReflectionParameter */
                    if ($parameter->getClassName()) {
                        $dependencies[$parameter->getName()] = $parameter->getClassName();
                    }
                }
            }

            if ($dependencies) {
                $classString .= $this->formatLine('==');
                $classString .= $this->dependencyWriter->writeProperties($dependencies);
            }
        }


        $classString .= $this->formatLine('}');

        if ($class->getParentClassName()) {
            $classString .= $this->formatLine(
                $this->writeObjectType($class) . ' ' . $this->formatClassName($class->getName()) . ' extends '
                . $this->formatClassName(
                    $class->getParentClassName()
                )
            );
        }

        if ($interfaceNames = $class->getOwnInterfaceNames()) {
            foreach ($interfaceNames as $interfaceName) {
                $classString .= $this->formatLine(
                    $this->writeObjectType($class) . ' ' . $this->formatClassName($class->getName()) . ' implements '
                    . $this->formatClassName(
                        $interfaceName
                    )
                );
            }
        }

        if ($this->dependencyWriter && $dependencies) {
            $classString .= $this->dependencyWriter->writeDependencies($class, $dependencies);
        }

        return $classString;
    }

    /**
     * @param IReflectionClass $class
     *
     * @return string
     */
    private function writeAbstract(IReflectionClass $class)
    {
        $return = '';
        if (true === $class->isAbstract() && false === $class->isInterface()) {
            $return = 'abstract ';
        }
        return $return;
    }

    /**
     * @param IReflectionClass $class
     *
     * @return string
     */
    private function writeObjectType(IReflectionClass $class)
    {
        $return = 'class';
        if (true === $class->isInterface()) {
            $return = 'interface';
        }
        return $return;
    }
}

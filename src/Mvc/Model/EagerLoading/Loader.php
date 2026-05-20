<?php

declare(strict_types=1);

/**
 * This file is part of the Phalcon Kit.
 *
 * (c) Phalcon Kit Team
 *
 * For the full copyright and license information, please view the LICENSE.txt
 * file that was distributed with this source code.
 */

namespace PhalconKit\Mvc\Model\EagerLoading;

use Phalcon\Di\Di;
use Phalcon\Mvc\Model\Relation;
use Phalcon\Mvc\Model\Resultset\Complex;
use Phalcon\Mvc\Model\Resultset\Simple;
use Phalcon\Mvc\Model\ResultsetInterface;
use Phalcon\Mvc\Model\Row;
use Phalcon\Mvc\ModelInterface;

final class Loader
{
    public ?array $subject;
    
    public string $className;
    
    public array $eagerLoads;
    
    public bool $singleModel;
    
    public array $options = [];
    
    private const string E_INVALID_SUBJECT = 'Expected value of `subject` to be either a ModelInterface object, a Simple object or an array of ModelInterface objects.';
    private const string E_INVALID_CLASSNAME = 'Expected value of `className` to be either an existing class name.';
    
    /**
     * Constructs a new instance of the class.
     *
     * @param mixed $from The data source from which to load the data. Can be an instance of ModelInterface,
     *                    Simple, array, null, or boolean.
     * @param array ...$arguments Optional arguments for eager loading. Each argument should be an array
     *                            specifying the relationships to eager load.
     *
     * @throws \InvalidArgumentException If the supplied data source is invalid.
     */
    public function __construct(mixed $from, array ...$arguments)
    {
        $error = false;
        $className = null;
        $this->singleModel = false;
        
        // Handle Model Interface
        if ($from instanceof ModelInterface) {
            $className = get_class($from);
            $from = [$from];
            $this->singleModel = true;
        }
        
        // Handle Simple Resultset
        elseif ($from instanceof Simple) {
            $from = iterator_to_array($from);
            if (isset($from[0])) {
                $className = get_class($from[0]);
            }
            else {
                $from = null;
            }
        }
        
        // Handle Complex Resultset
        elseif ($from instanceof Complex) {
            // we will consider the first model to be the main model
            $tmp = [];
            foreach ($from as $row) {
                assert($row instanceof Row);
                $array = $row->toArray();
                $firstModel = reset($array);
                $tmp [] = $firstModel;
            }
            $from = $tmp;
            if (isset($from[0])) {
                $className = get_class($from[0]);
            }
            else {
                $from = null;
            }
        }
        
        // Handle array
        elseif (is_array($from)) {
            $from = array_filter($from);
            if (isset($from[0])) {
                $className = get_class($from[0]);
            }
            foreach ($from as $el) {
                if ($el instanceof ModelInterface) {
                    // elements must be all the same model class
                    if ($className !== get_class($el)) {
                        $error = true;
                        break;
                    }
                }
                else {
                    // element must be a ModelInterface
                    $error = true;
                    break;
                }
            }
            if (empty($from)) {
                $from = null;
            }
        }
        
        // Handle null or empty
        elseif (is_null($from) || is_bool($from)) {
            $from = null;
        }
        
        // error
        else {
            $error = true;
        }
        
        if ($error) {
            throw new \InvalidArgumentException(self::E_INVALID_SUBJECT);
        }
        if (!isset($className) || !class_exists($className)) {
            throw new \InvalidArgumentException(self::E_INVALID_CLASSNAME);
        }
        
        $this->subject = $from;
        $this->className = $className;
        $this->eagerLoads = ($from === null || empty($arguments)) ? [] : self::parseArguments($arguments);
    }
    
    /**
     * Sets the options for the current object instance.
     *
     * @param array $options An array of options for the current object.
     * @return $this The current object instance after setting the options.
     */
    public function setOptions(array $options = []): self
    {
        $this->options = $options;
        return $this;
    }
    
    /**
     * Sets the subject of the object.
     *
     * @param array|null $subject The subject data array or null to clear the subject.
     *
     * @return $this The current object instance with the subject set.
     */
    public function setSubject(?array $subject): self
    {
        $this->subject = $subject;
        return $this;
    }
    
    /**
     * Gets the subject
     *
     * @return ModelInterface[]|null The subject, or null if it has not been set.
     */
    public function getSubject(): ?array
    {
        return $this->subject;
    }
    
    /**
     * Retrieves the first element from the subject array and returns it.
     *
     * @return ModelInterface|null The first element from the subject array, or null if the array is empty.
     */
    public function getFirstSubject(): ?ModelInterface
    {
        return $this->subject[0] ?? null;
    }
    
    /**
     * Creates an instance of the current object from various input types and returns it.
     *
     * @param mixed $subject The input object or array to create the instance from.
     * @param mixed ...$arguments Additional arguments that can be passed to the creation process.
     * 
     * @return array|ModelInterface|null The current object instance created from the input.
     */
    public static function from(mixed $subject, mixed ...$arguments): array|ModelInterface|null
    {
        if ($subject instanceof ModelInterface) {
            return self::fromModel($subject, ...$arguments);
        }
        
        else if ($subject instanceof ResultsetInterface) {
            return self::fromResultset($subject, ...$arguments);
        }
        
        else if (is_array($subject)) {
            return self::fromArray($subject, ...$arguments);
        }
        
        throw new \InvalidArgumentException(Loader::E_INVALID_SUBJECT);
    }
    
    /**
     * Create and get from a Model
     *
     * @param ModelInterface $subject
     * @param mixed ...$arguments
     * @return ?ModelInterface
     */
    public static function fromModel(ModelInterface $subject, mixed ...$arguments): ?ModelInterface
    {
        return (new self($subject, ...$arguments))->execute()->getFirstSubject();
    }
    
    /**
     * Create and get from an array
     *
     * @param ModelInterface[] $subject
     * @param mixed ...$arguments
     * @return array
     */
    public static function fromArray(array $subject, mixed ...$arguments): array
    {
        return (new self($subject, ...$arguments))->execute()->getSubject() ?? [];
    }
    
    /**
     * Create and get from a Model without soft deleted records
     *
     * @param ModelInterface $subject
     * @param mixed ...$arguments
     * @return ?ModelInterface
     */
    public static function fromModelWithoutSoftDelete(ModelInterface $subject, mixed ...$arguments): ?ModelInterface
    {
        $options = ['softDelete' => 'softDelete'];
        $obj = new self($subject, ...$arguments);
        return $obj->setOptions($options)->execute()->getFirstSubject();
    }
    
    /**
     * Create and get from an array without soft deleted records
     *
     * @param ModelInterface[] $subject
     * @param mixed ...$arguments
     * @return array
     */
    public static function fromArrayWithoutSoftDelete(array $subject, mixed ...$arguments): array
    {
        $options = ['softDelete' => 'softDelete'];
        $obj = new self($subject, ...$arguments);
        return $obj->setOptions($options)->execute()->getSubject() ?? [];
    }
    
    /**
     * Create and get from a Resultset
     *
     * @param ResultsetInterface $subject
     * @param mixed ...$arguments
     * @return array
     */
    public static function fromResultset(ResultsetInterface $subject, mixed ...$arguments): array
    {
        return (new self($subject, ...$arguments))->execute()->getSubject() ?? [];
    }
    
    /**
     * Parses the arguments that will be resolved to Relation instances
     *
     * @param array $arguments
     * @return array
     * @throws \InvalidArgumentException
     */
    private static function parseArguments(array $arguments): array
    {
        if (empty($arguments)) {
            throw new \InvalidArgumentException('Arguments can not be empty');
        }
        
        $relations = [];
        if (count($arguments) === 1 && isset($arguments[0]) && is_array($arguments[0])) {
            foreach ($arguments[0] as $relationAlias => $queryConstraints) {
                if (is_string($relationAlias)) {
                    $relations[$relationAlias] = is_callable($queryConstraints) ? $queryConstraints : null;
                }
                elseif (is_string($queryConstraints)) {
                    $relations[$queryConstraints] = null;
                }
            }
        }
        else {
            foreach ($arguments as $relationAlias) {
                if (is_string($relationAlias)) {
                    $relations[$relationAlias] = null;
                }
            }
        }
        
        return $relations;
    }
    
    /**
     * Adds an eager load for a given relation alias and optional constraints and returns an instance of the current object.
     *
     * @param string $relationAlias The alias of the relation to be eagerly loaded.
     * @param callable|null $constraints Optional. The callback function that applies constraints on the eager loaded relation. Default is null.
     * 
     * @return $this The current object instance after adding the eager load.
     */
    public function addEagerLoad(string $relationAlias, ?callable $constraints = null): self
    {
        $this->eagerLoads[$relationAlias] = $constraints;
        return $this;
    }
    
    /**
     * Resolves the relations
     *
     * @return EagerLoad[]
     * @throws \RuntimeException
     */
    private function buildTree(): array
    {
        uksort($this->eagerLoads, 'strcmp');
        
        $di = Di::getDefault();
        $mM = $di['modelsManager'];
        
        $eagerLoads = $resolvedRelations = [];
        
        foreach ($this->eagerLoads as $relationAliases => $queryConstraints) {
            $nestingLevel = 0;
            $relationAliases = explode('.', $relationAliases);
            $nestingLevels = count($relationAliases);
            
            do {
                do {
                    $alias = $relationAliases[$nestingLevel];
                    $name = implode('.', array_slice($relationAliases, 0, $nestingLevel + 1));
                    if (isset($eagerLoads[$name])) {
                        $nestingLevel++;
                    }
                }
                while (isset($eagerLoads[$name]));
                
                if ($nestingLevel === 0) {
                    $parentClassName = $this->className;
                    $parentName = null;
                }
                else {
                    $parentName = implode('.', array_slice($relationAliases, 0, $nestingLevel));
                    $parentClassName = $resolvedRelations[$parentName]->getReferencedModel();
                    
                    if ($parentClassName[0] === '\\') {
                        $parentClassName = ltrim($parentClassName, '\\');
                    }
                }
                
                if (!isset($resolvedRelations[$name])) {
                    $mM->load($parentClassName);
                    $relation = $mM->getRelationByAlias($parentClassName, $alias);
                    
                    if (!$relation instanceof Relation) {
                        throw new \RuntimeException(sprintf(
                            'There is no defined relation for the model `%s` using alias `%s`',
                            $parentClassName,
                            $alias
                        ));
                    }
                    
                    $resolvedRelations[$name] = $relation;
                }
                else {
                    $relation = $resolvedRelations[$name];
                }
                
                $relType = $relation->getType();
                
                if ($relType !== Relation::BELONGS_TO &&
                    $relType !== Relation::HAS_ONE &&
                    $relType !== Relation::HAS_MANY &&
                    $relType !== Relation::HAS_MANY_THROUGH
                ) {
                    throw new \RuntimeException(sprintf('Unknown relation type `%s`', $relType));
                }
                
                // @todo allow composite keys
//                if (is_array($relation->getFields()) ||
//                    is_array($relation->getReferencedFields())
//                ) {
//                    throw new \RuntimeException('Relations with composite keys are not supported');
//                }
                
                $parent = $parentName !== null ? $eagerLoads[$parentName] : $this;
                $constraints = $nestingLevel + 1 === $nestingLevels ? $queryConstraints : null;
                
                $eagerLoads[$name] = new EagerLoad($relation, $constraints, $parent);
            }
            while (++$nestingLevel < $nestingLevels);
        }
        
        return $eagerLoads;
    }
    
    /**
     * Execute the eager loading of related models.
     *
     * This method iterates through the result of the buildTree method and loads the related models
     * using the load method of each eager load instance.
     *
     * @return self The instance of the class executing the method.
     */
    public function execute(): self
    {
        foreach ($this->buildTree() as $eagerLoad) {
            // @todo option to enable or disable soft delete etc.
//            $eagerLoad->load($this->options); 
            $eagerLoad->load();
        }
        
        return $this;
    }
    
    /**
     * Loads the data from a data source and returns an instance of the current object.
     *
     * @return self The current object instance after loading the data.
     */
    public function load(): self
    {
        return $this->execute();
    }
}

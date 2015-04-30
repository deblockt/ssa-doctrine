<?php
namespace ssa\runner\resolver;

use ssa\runner\resolver\impl\DefaultObjectResolver;
use Doctrine\ORM\EntityManager;

/**
 * find parameter into database for doctrine entity
 *
 * @author thomas
 */
class DoctrineObjectResolver extends DefaultObjectResolver {
    
    private $em;
    
    public function __construct(EntityManager $em) {
        $this->em = $em;
    }
    
    protected function canResolve(\ReflectionClass $class) {
        try {
			$meta = $this->em->getMetadataFactory()->getMetadataFor($class->getName());
			return isset($meta->isMappedSuperclass) && $meta->isMappedSuperclass === false;
		} catch (\Exception $e){
			return false;
		}
    }
	
    /**
     * function class for instancate the class
     * @param \ReflectionClass $class the class name
     * @param array $parameters the parameters
     */
    protected function instanciate(\ReflectionClass $class, $parameters) {
        $metaData =  $this->em->getMetadataFactory()->getMetadataFor($class->getName());
        $identifiers = $metaData->getIdentifier();
        $repository = $this->em->getRepository($metaData->getName());
        $idValue = array();
        foreach ($identifiers as $identifier) {
            if (isset($parameters[$identifier])) {
                if (is_array($parameters[$identifier])) {
					foreach ($parameters[$identifier] as $key => $value) {
						$idValue[$identifier] = $value;
					}
				} else {
					$idValue[$identifier] = $parameters[$identifier];
				}
            } else {
                return parent::instanciate($class, $parameters);
            }
        }
        
        $find =  $repository->find($idValue);
        if ($find === NULL) {
            return parent::instanciate($class, $parameters);
        }
        return $find;
    }
}
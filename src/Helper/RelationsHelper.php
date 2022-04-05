<?php

namespace Unlooped\GridBundle\Helper;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\Mapping\ClassMetadata;
use ReflectionException;
use Unlooped\GridBundle\Struct\FieldMetaDataStruct;
use function Symfony\Component\String\u;

class RelationsHelper
{
    /** @var FieldMetaDataStruct[] */
    protected static array $fieldAliases = [];

    public static function cloneAliases(QueryBuilder $orig, QueryBuilder $new, string $entity): void
    {
        $origKeyPrefix = self::getKeyPrefix($orig, $entity);
        $newKeyPrefix = self::getKeyPrefix($new, $entity);

        foreach (self::$fieldAliases as $key => $fieldAlias) {
            $uKey = u($key);
            if ($uKey->startsWith($origKeyPrefix)) {
                $newKey = $uKey->replace($origKeyPrefix, $newKeyPrefix)->toString();
                self::$fieldAliases[$newKey] = $fieldAlias;
            }
        }
    }

    public static function getAliasForEntityAndField(QueryBuilder $qb, string $entity, string $path)
    {
        $key = self::getKeyForEntityAndField($qb, $entity, $path);
        if (\array_key_exists($key, self::$fieldAliases)) {
            return self::$fieldAliases[$key];
        }

        $rootAlias = $qb->getRootAliases()[0];
        $pieces    = explode('.', $path);
        $le        = array_pop($pieces);

        array_unshift($pieces, $rootAlias);

        return implode('_', $pieces).'.'.$le;
    }

    /**
     * @throws ReflectionException
     * @throws \Doctrine\ORM\Mapping\MappingException
     */
    public static function joinRequiredPaths(QueryBuilder $qb, string $entity, string $path): FieldMetaDataStruct
    {
        $keyPrefix = self::getKeyPrefix($qb, $entity);
        $key       = self::getKeyForEntityAndField($qb, $entity, $path);

        if (\array_key_exists($key, self::$fieldAliases)) {
            return self::$fieldAliases[$key];
        }

        $fields = explode('.', $path);
        $alias  = $qb->getRootAliases()[0];

        $md = self::getMetadataForEntity($qb, $entity);

        if (1 === \count($fields)) {
            $fieldData = null;
            if ($md->hasAssociation($fields[0])) {
                $fieldData = $md->getAssociationMapping($fields[0]);
            }

            return FieldMetaDataStruct::create($alias.'.'.$fields[0], $fieldData);
        }

        foreach ($fields as $field) {
            if ($md->hasAssociation($field)) {
                $nAlias             = $alias.'_'.$field;
                $associationMapping = $md->getAssociationMapping($field);
                $md                 = self::getMetadataForEntity($qb, $associationMapping['targetEntity']);

                if (\array_key_exists($keyPrefix.$nAlias, self::$fieldAliases)) {
                    $alias = self::$fieldAliases[$keyPrefix.$nAlias]->alias;
                } else {
                    $qb->leftJoin($alias.'.'.$field, $nAlias);
                    self::$fieldAliases[$keyPrefix.$nAlias] = FieldMetaDataStruct::create($nAlias, $associationMapping);
                    $alias                                  = $nAlias;
                }

                continue;
            }

            $alias .= '.'.$field;

            break;
        }

        $fmds = FieldMetaDataStruct::create($alias);

        self::$fieldAliases[$key] = $fmds;

        return $fmds;
    }

    protected static function getKeyPrefix(QueryBuilder $qb, string $entity): string
    {
        return spl_object_hash($qb).'::'.$entity.'::';
    }

    protected static function getKeyForEntityAndField(QueryBuilder $qb, string $entity, string $path): string
    {
        return self::getKeyPrefix($qb, $entity).$path;
    }

    /**
     * @throws ReflectionException
     * @throws \Doctrine\Persistence\Mapping\MappingException
     */
    protected static function getMetadataForEntity(QueryBuilder $qb, $entity): ClassMetadata
    {
        /** @var EntityManager $em */
        $em = $qb->getEntityManager();

        return $em->getMetadataFactory()->getMetadataFor($entity);
    }
}

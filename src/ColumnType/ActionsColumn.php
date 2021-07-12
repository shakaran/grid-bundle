<?php

namespace Unlooped\GridBundle\ColumnType;

use Exception;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ActionsColumn extends AbstractColumnType
{
    protected $template = '@UnloopedGrid/column_types/actions.html.twig';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'actions'    => [],
            'isMapped'   => false,
            'isSortable' => false,
        ]);

        $resolver->setAllowedTypes('actions', ['array']);
    }

    public function getValue(string $field, object $object, array $options = [])
    {
        try {
            return $this->propertyAccessor->getValue($object, $field);
        } catch (Exception $e) {
            return null;
        }
    }

}

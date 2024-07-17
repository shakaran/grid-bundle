<?php

namespace Unlooped\GridBundle\ColumnType;

use DateTimeZone;
use Symfony\Component\Intl\Locales;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LocalizedDateColumn extends AbstractColumnType
{
    protected string $template = '@UnloopedGrid/column_types/localized_date.html.twig';

    public function configureOptions(OptionsResolver $resolver): void
    {
        parent::configureOptions($resolver);

        $resolver->setDefaults([
            'attr'        => ['class' => 'text-right'],
            'header_attr' => ['class' => 'text-right'],
            'date_format' => 'medium',
            'time_format' => 'medium',
            'locale'      => null,
            'timezone'    => null,
            'format'      => '',
            'calendar'    => 'gregorian',
        ]);

        $resolver->setAllowedTypes('date_format', 'string');
        $resolver->setAllowedValues('date_format', ['none', 'short', 'medium', 'long', 'full']);
        $resolver->setAllowedTypes('time_format', 'string');
        $resolver->setAllowedValues('time_format', ['none', 'short', 'medium', 'long', 'full']);

        $locales   = Locales::getLocales();
        $locales[] = null;
        $resolver->setAllowedTypes('locale', ['null', 'string']);
        $resolver->setAllowedValues('locale', $locales);

        $timeZones   = DateTimeZone::listIdentifiers();
        $timeZones[] = null;
        $resolver->setAllowedTypes('timezone', ['null', 'string']);
        $resolver->setAllowedValues('timezone', $timeZones);

        $resolver->setAllowedTypes('format', ['null', 'string']);
        $resolver->setAllowedTypes('calendar', ['null', 'string']);
        $resolver->setAllowedValues('calendar', ['gregorian', 'traditional']);
    }
}

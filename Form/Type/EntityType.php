<?php

namespace Experium\ExtraBundle\Form\Type;

use Symfony\Component\Form\FormBuilder;
use Symfony\Bridge\Doctrine\Form\EventListener\MergeCollectionListener;
use Experium\ExtraBundle\Form\ChoiceList\EntityChoiceList;
use Experium\ExtraBundle\Form\DataTransformer\EntitiesToArrayTransformer;
use Experium\ExtraBundle\Form\DataTransformer\EntityToIdTransformer;
use Symfony\Component\Form\AbstractType;

class EntityType extends AbstractType
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilder $builder, array $options)
    {
        if ($options['multiple']) {
            throw new \InvalidArgumentException('Multiple temporary not supported');
            $builder
                ->addEventSubscriber(new MergeCollectionListener())
                ->prependClientTransformer(new EntitiesToArrayTransformer($options['choice_list']))
            ;
        } else {
            $builder->prependClientTransformer(new EntityToIdTransformer($options['choice_list']));
        }
    }

    public function getDefaultOptions(array $options)
    {
        $defaultOptions = array(
            'em'                => null,
            'class'             => null,
            'callable'          => null,
            'entity_collection' => null,
            'choices'           => array(),
        );

        $options = array_replace($defaultOptions, $options);

        if (!isset($options['choice_list'])) {
            $defaultOptions['choice_list'] = new EntityChoiceList(
                $this->container->get(($options['em'] ?: $this->container->get('kernel')->getName()).'.entity_manager'),
                $options['class'],
                $options['entity_collection'],
                $options['callable'],
                $options['choices']
            );
        }

        return $defaultOptions;
    }

    public function getParent(array $options)
    {
        return 'choice';
    }

    public function getName()
    {
        return 'entity';
    }
}

<?php

namespace Experium\ExtraBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Experium\ExtraBundle\Form\ChoiceList\EntityChoiceList;
use Experium\ExtraBundle\Form\DataTransformer\EntityToIdTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Symfony\Component\Form\Extension\Core\EventListener\MergeCollectionListener;
use Experium\ExtraBundle\Form\DataTransformer\EntitiesToArrayTransformer;

class EntityType extends AbstractType
{
    private $container;

    public function __construct($container)
    {
        $this->container = $container;
    }

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['multiple']) {
            throw new \InvalidArgumentException('Multiple temporary not supported');
        } else {
            $builder->addViewTransformer(new EntityToIdTransformer($options['choice_list']), true);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'choice';
    }

    public function getName()
    {
        return 'experium_entity';
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(array(
            'em'                => null,
            'class'             => null,
            'callable'          => null,
            'entity_collection' => null,
            'choices'           => array(),
            'choice_list'       => function (Options $options, $value) {
                return $options['class'] ? new EntityChoiceList(
                    $this->container->get(($options['em'] ?: $this->container->get('kernel')->getName()).'.entity_manager'),
                    $options['class'],
                    $options['entity_collection'],
                    $options['callable'],
                    $options['choices']
                ) : $value;
            }
        ));
    }
}

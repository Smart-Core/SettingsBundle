<?php

namespace SmartCore\Bundle\SettingsBundle\Form\Type;

use Smart\CoreBundle\Form\DataTransformer\BooleanToStringTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingBoolFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add($builder
                ->create('value', 'checkbox', ['required' => false])
                ->addModelTransformer(new BooleanToStringTransformer())
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => 'SmartCore\Bundle\SettingsBundle\Entity\Setting',
        ]);
    }

    public function getName()
    {
        return 'smart_core_settings_bool';
    }
}

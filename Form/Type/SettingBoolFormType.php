<?php

namespace SmartCore\Bundle\SettingsBundle\Form\Type;

use Smart\CoreBundle\Form\DataTransformer\BooleanToStringTransformer;
use SmartCore\Bundle\SettingsBundle\Entity\Setting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingBoolFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add($builder
                ->create('value', CheckboxType::class, ['required' => false])
                ->addModelTransformer(new BooleanToStringTransformer())
            )
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Setting::class,
        ]);
    }

    public function getBlockPrefix()
    {
        return 'smart_core_setting_bool';
    }
}

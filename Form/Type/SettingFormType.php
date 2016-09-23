<?php

namespace SmartCore\Bundle\SettingsBundle\Form\Type;

use Smart\CoreBundle\Form\DataTransformer\HtmlTransformer;
use SmartCore\Bundle\SettingsBundle\Entity\Setting;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SettingFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add($builder
                ->create('value', TextType::class, ['attr' => ['autofocus' => 'autofocus']])
                ->addViewTransformer(new HtmlTransformer(false))
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
        return 'smart_core_settings';
    }
}

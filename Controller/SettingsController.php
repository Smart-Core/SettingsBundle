<?php

namespace SmartCore\Bundle\SettingsBundle\Controller;

use Smart\CoreBundle\Controller\Controller;
use Smart\CoreBundle\Form\DataTransformer\BooleanToStringTransformer;
use Smart\CoreBundle\Form\DataTransformer\HtmlTransformer;
use SmartCore\Bundle\SettingsBundle\Manager\SettingsManager;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;

class SettingsController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        return $this->render('@SmartSettings/Settings/index.html.twig', [
            'settings' => $this->get('settings')->all(),
        ]);
    }

    /**
     * @param Request $request
     * @param int     $id
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, $id)
    {
        /** @var SettingsManager $settingsManager */
        $settingsManager = $this->get('settings');

        $setting = $settingsManager->findById($id);

        if (empty($setting)) {
            throw $this->createNotFoundException();
        }

        $builder = $this->createFormBuilder($setting);

        $formType    = TextType::class;
        $formOptions = [];

        $settingConfig = $settingsManager->getSettingConfig($setting);

        if (is_array($settingConfig)) {
            if (isset($settingConfig['type'])) {
                if (class_exists($settingConfig['type'])) {
                    $formType = $settingConfig['type'];
                } elseif (class_exists('Symfony\Component\Form\Extension\Core\Type\\'.$settingConfig['type'])) {
                    $formType = 'Symfony\Component\Form\Extension\Core\Type\\'.$settingConfig['type'];
                } else {
                    throw new \Exception("Unknown form type: '{$settingConfig['type']}'");
                }
            }
        }

        $formOptions['attr'] = [
            'autofocus' => 'autofocus',
            'class' => 'focused'
        ];

        $formOptions['required'] = $settingsManager->getSettingOption($setting, 'required', true);

        $constraintsObjects = [];
        foreach ($settingsManager->getSettingOption($setting, 'validation', []) as $key => $constraints) {
            foreach ($constraints as $constraintClass => $constraintParams) {
                $_class = '\Symfony\Component\Validator\Constraints\\'.$constraintClass;

                $constraintsObjects[] = new $_class($constraintParams);
            }
        }
        $formOptions['constraints'] = $constraintsObjects;

        switch ($formType) {
            case CheckboxType::class:
                $formOptions['required'] = false;
                $builder
                    ->add($builder
                        ->create('value', $formType, $formOptions)
                        ->addModelTransformer(new BooleanToStringTransformer())
                    )
                ;
                break;
            case TextType::class:
                $builder
                    ->add($builder
                        ->create('value', $formType, $formOptions)
                        ->addViewTransformer(new HtmlTransformer(false))
                    )
                ;
                break;
            case ChoiceType::class:
                $formOptions['choices'] = array_flip($settingsManager->getSettingOption($setting, 'choices', []));
            default:
                $builder->add('value', $formType, $formOptions);
        }

        $form = $builder
            ->add('update', SubmitType::class, ['attr' => ['class' => 'btn btn-success']])
            ->add('cancel', SubmitType::class, ['attr' => ['class' => 'btn btn-default', 'formnovalidate' => 'formnovalidate']])
            ->getForm();

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->get('cancel')->isClicked()) {
                return $this->redirectToRoute('smart_core_settings');
            }

            if ($form->isValid()) {
                $setting = $form->getData();

                if ($this->get('settings')->updateEntity($setting)) {
                    $this->addFlash('success', "Настройка <b>".$setting->getName()."</b> обновлена.");
                } else {
                    $this->addFlash('warning', "Настройка <b>".$setting->getName()."</b> не обновлена.");
                }

                return $this->redirectToRoute('smart_core_settings');
            }
        }

        return $this->render('@SmartSettings/Settings/edit.html.twig', [
            'form'    => $form->createView(),
            'setting' => $setting,
        ]);
    }

    /**
     * @param Request $request
     * @param int     $id
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function historyAction($id)
    {
        /** @var SettingsManager $settingsManager */
        $settingsManager = $this->get('settings');

        $setting = $settingsManager->findById($id);

        if (empty($setting)) {
            throw $this->createNotFoundException();
        }

        return $this->render('@SmartSettings/Settings/history.html.twig', [
            'setting' => $setting,
        ]);
    }

    /**
     * @param $id
     */
    public function rollbackAction($id)
    {
        /** @var SettingsManager $settingsManager */
        $settingsManager = $this->get('settings');

        $historyItem = $settingsManager->findHistoryById($id);

        if (empty($historyItem)) {
            throw $this->createNotFoundException();
        }

        if ($historyItem) {
            $setting = $historyItem->getSetting();
            $setting->setValue($historyItem->getValue());

            if ($this->get('settings')->updateEntity($setting)) {
                $this->addFlash('success', 'Откат успешно выполнен.');
            } else {
                $this->addFlash('warning', "Настройка <b>".$setting->getName()."</b> не обновлена.");
            }
        } else {
            $this->addFlash('error', 'Непредвиденная ошибка при выполнении отката');
        }

        return $this->redirect($this->generateUrl('smart_core_settings'));
    }
}

<?php

namespace SmartCore\Bundle\SettingsBundle\Controller;

use SmartCore\Bundle\SettingsBundle\Entity\Setting;
use SmartCore\Bundle\SettingsBundle\Form\Type\SettingBoolFormType;
use SmartCore\Bundle\SettingsBundle\Form\Type\SettingFormType;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

class SettingsController extends Controller
{
    /**
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function indexAction()
    {
        return $this->render('SmartSettingsBundle:Settings:index.html.twig', [
            'settings' => $this->get('settings')->all(),
        ]);
    }

    /**
     * @param Request $request
     * @param Setting $setting
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse|\Symfony\Component\HttpFoundation\Response
     */
    public function editAction(Request $request, Setting $setting)
    {
        switch ($setting->getType()) {
            case Setting::TYPE_BOOL:
                $form = $this->createForm(new SettingBoolFormType(), $setting);
                break;
            default:
                $form = $this->createForm(new SettingFormType(), $setting);
        }

        $form->add('update', 'submit', ['attr' => ['class' => 'btn btn-success']]);
        $form->add('cancel', 'submit', ['attr' => ['class' => 'btn-default', 'formnovalidate' => 'formnovalidate']]);

        if ($request->isMethod('POST')) {
            $form->handleRequest($request);

            if ($form->get('cancel')->isClicked()) {
                return $this->redirectToRoute('smart_core_settings');
            }

            if ($form->isValid()) {
                $this->get('settings')->updateEntity($form->getData());
                $this->addFlash('success', 'Настройка обновлена');

                return $this->redirectToRoute('smart_core_settings');
            }
        }

        return $this->render('SmartSettingsBundle:Settings:edit.html.twig', [
            'form'    => $form->createView(),
            'setting' => $setting,
        ]);
    }
}

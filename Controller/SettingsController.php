<?php

namespace SmartCore\Bundle\SettingsBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class SettingsController extends Controller
{
    public function indexAction()
    {
        return $this->render('SmartSettingsBundle:Settings:index.html.twig', [
            'settings' => $this->get('settings')->all(),
        ]);
    }

    public function editAction($id)
    {
        return $this->render('SmartSettingsBundle:Settings:edit.html.twig', [
            'setting' => $this->get('settings')->findById($id),
        ]);
    }
}

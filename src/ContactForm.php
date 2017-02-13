<?php

namespace Bolt\Extension\Postitief\ContactForm;

use Bolt\Asset\File\JavaScript;
use Bolt\Controller\Zone;
use Bolt\Extension\SimpleExtension;
use Silex\Application;
use Silex\ControllerCollection;
use Symfony\Component\HttpFoundation\Request;

/**
 * ContactForm extension class.
 *
 * @author Dybo <d.boertje@postitief.nl>
 */
class ContactForm extends SimpleExtension
{
    /**
     * Register routes for the frontend
     *
     * @param ControllerCollection $collection
     */
    protected function registerFrontendRoutes(ControllerCollection $collection)
    {
        $collection->post('/api/contact/submit', [$this, 'submitContactForm']);
    }

    /**
     * Register assets needed for the contactform.
     *
     * @return array
     */
    protected function registerAssets()
    {
        return [
            (
                (new JavaScript('contact.js'))
                    ->setLate(true)
                    ->setPriority(99)
                    ->setZone(Zone::FRONTEND)
            ),
        ];
    }

    /**
     * Submit contact form.
     *
     * @param Application $application
     * @param Request $request
     * @return \Symfony\Component\HttpFoundation\JsonResponse
     */
    public function submitContactForm(Application $application, Request $request)
    {
        $data = $this->retreiveFormData($request);
        $validation = $this->validateFields($data);

        if(count($validation) > 0) {
            return $application->json([
                'errors' => $validation,
            ], 200);
        }

        return $this->sendEmail($data);
    }

    /**
     * Get form data from the Request
     *
     * @param Request $request
     * @return \stdClass
     */
    private function retreiveFormData(Request $request)
    {
        $data = new \stdClass();

        $data->name = $request->get('name');
        $data->email = $request->get('email');
        $data->telephone = $request->get('telephone');
        $data->message = $request->get('message');

        return $data;
    }

    /**
     * Render the email template.
     *
     * @param $data
     * @return string
     */
    private function renderEmail($data)
    {
        return $this->renderTemplate('email.twig', [
            'name' => $data->name,
            'email' => $data->email,
            'telephone' => $data->telephone,
            'message' => $data->message
        ]);
    }

    /**
     * Send the email using the built in mailer.
     *
     * @param $data
     * @return mixed
     */
    private function sendEmail($data)
    {
        $html = $this->renderEmail($data);

        try {
            $message = $this->getContainer()['mailer']
                ->createMessage('message')
                ->setSubject($this->getConfig()['subject'])
                ->setFrom($this->getConfig()['from'])
                ->setReplyTo([
                    $data->email => $data->name,
                ])
                ->setTo($this->getConfig()['email'])
                ->setBody($html, 'text/html')
                ->addPart(strip_tags($html), 'text/plain');

            $this->getContainer()['mailer']->send($message);

            $response = $this->getContainer()->json([
                'message' => 'Message Sent!'
            ], 200);

        } catch(\Exception $e) {

            $error = "The 'mailoptions' need to be set in app/config/extensions/contactform.postitief.yml";

            $this->getContainer()['logger.system']->error($error, ['event' => 'config']);

            $response = $this->getContainer()->json([
                'message' => $error,
            ], 500);
        }

        return $response;
    }

    /**
     * Validate the form fields.
     *
     * @param $data
     * @return array
     */
    private function validateFields($data)
    {
        $errors = [];

        if (!preg_match("/[-0-9a-zA-Z ]{2,60}/", $data->name)) {
            $errors['name'] = 'Naam is een verplicht veld';
        }

        if (!preg_match("/[-0-9a-zA-Z.+_]+@[-0-9a-zA-Z.+_]/", $data->email)) {
            $errors['email'] = 'Email is ongeldig';
        }

        if (!preg_match("/[-0-9a-zA-Z .]{2,2000}/", $data->message)) {
            $errors['message'] = 'Het bericht mag niet langer zijn dan 2000 karakters.';
        }

        return $errors;
    }
}

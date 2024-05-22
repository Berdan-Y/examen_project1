<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Form\BarberAppointmentType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class EmployeeController extends AbstractController
{
    #[Route('/employee', name: 'app_employee')]
    public function index(EntityManagerInterface $em): Response
    {
        $barber = $this->getUser();
        $appointments = $em->getRepository(Appointment::class)->findBy(['barber' => $barber]);
        return $this->render('employee/index.html.twig', [
            'appointments' => $appointments,
        ]);
    }

    #[Route('/employee/appointment/update/{id}', name: 'app_employee_appointment_update')]
    public function updateAppointment(EntityManagerInterface $em, Request $request, int $id)
    {
        $message = 'Update appointment';
        $appointment = $em->getRepository(Appointment::class)->find($id);

        $form = $this->createForm(BarberAppointmentType::class, $appointment);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($appointment);
            $em->flush();
            $this->addFlash('success', 'Successfully updated a appointment');

            return $this->redirectToRoute('app_employee');
        }

        return $this->render('forms/handleForms.html.twig', [
            'form' => $form,
            'message' => $message,
        ]);
    }

    #[Route('/employee/appointment/delete/{id}', 'app_employee_appointment_delete')]
    public function deleteAppointment(EntityManagerInterface $em, int $id)
    {
        $appointment = $em->getRepository(Appointment::class)->find($id);

        $em->remove($appointment);
        $em->flush();
        $this->addFlash('success', 'Successfully canceled appointment');

        return $this->redirectToRoute('app_employee');
    }
}

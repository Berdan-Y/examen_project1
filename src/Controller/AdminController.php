<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Category;
use App\Entity\Treatment;
use App\Entity\User;
use App\Form\AppointmentType;
use App\Form\CategoryType;
use App\Form\EmployeeType;
use App\Form\TreatmentType;
use Doctrine\Entity;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasher;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(EntityManagerInterface $em): Response
    {
        $appointments = $em->getRepository(Appointment::class)->findAll();
        return $this->render('admin/index.html.twig', [
            'appointments' => $appointments,
        ]);
    }

    #[Route('/admin/appointment/update/{id}', 'app_admin_appointment_update')]
    public function updateAppointment(EntityManagerInterface $em, Request $request, int $id)
    {
        $message = 'Update appointment';
        $appointments = $em->getRepository(Appointment::class)->find($id);

        $form = $this->createForm(AppointmentType::class, $appointments);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($appointments);
            $em->flush();
            $this->addFlash('success', 'Successfully changed a appointment');
            return $this->redirectToRoute('app_admin');
        }

        return $this->render('forms/handleForms.html.twig', [
            'form' => $form,
            'message' => $message,
        ]);
    }

    #[Route('/admin/appointment/delete/{id}', 'app_admin_appointment_delete')]
    public function deleteAppointment(EntityManagerInterface $em, int $id)
    {
        $appointment = $em->getRepository(Appointment::class)->find($id);
        $em->remove($appointment);
        $em->flush();
        $this->addFlash('success', 'Successfully canceled a appointment');
        return $this->redirectToRoute('app_admin');
    }

    #[Route('/admin/category', name: 'app_admin_category')]
    public function showCategory(EntityManagerInterface $em): Response
    {
        $categories = $em->getRepository(Category::class)->findAll();

        return $this->render('admin/category.html.twig', [
            'categories' => $categories,
        ]);
    }

    #[Route('/admin/category/insert', name: 'app_admin_category_insert')]
    public function addCategory(EntityManagerInterface $em, Request $request)
    {
        $message = 'Categorie toevoegen';
        $category = new Category();
        $form = $this->createForm(CategoryType::class, $category);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($category);
            $em->flush();
            $this->addFlash('success', 'Added a new category');

            return $this->redirectToRoute('app_admin_category');
        }

        return $this->render('forms/handleForms.html.twig', [
            'form' => $form,
            'message' => $message,
        ]);
    }

    #[Route('/admin/category/{id}', name: 'app_admin_treatments')]
    public function showTreatment(EntityManagerInterface $em, int $id)
    {
        $treatments = $em->getRepository(Treatment::class)->findBy(['category' => $id]);
        $categoryId = $id;

        return $this->render('admin/treatment.html.twig', [
            'treatments' => $treatments,
            'category_id' => $categoryId,
        ]);
    }

    #[Route('/admin/category/{id}/insert', name: 'app_admin_treatment_insert')]
    public function addTreatment(EntityManagerInterface $em, Request $request, int $id)
    {
        $message = 'Behandeling toevoegen';
        $treatment = new Treatment();
        $category = $em->getRepository(Category::class)->find($id);
        $form = $this->createForm(TreatmentType::class, $treatment);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $treatment->setCategory($category);
            $em->persist($treatment);
            $em->flush();
            $this->addFlash('success', 'Added a new treatment');

            return $this->redirectToRoute('app_admin_treatments', ['id' => $id]);
        }

        return $this->render('forms/handleForms.html.twig', [
            'form' => $form,
            'message' => $message,
        ]);
    }

    #[Route('/admin/category/{id}/update', name: 'app_admin_treatment_update')]
    public function updateTreatment(EntityManagerInterface $em, Request $request, int $id)
    {
        $message = 'Behandeling aanpassen';
        $treatment = $em->getRepository(Treatment::class)->find($id);
        $form = $this->createForm(TreatmentType::class, $treatment);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($treatment);
            $em->flush();
            $this->addFlash('success', 'Added a new treatment');

            return $this->redirectToRoute('app_admin_treatments', ['id' => $id]);
        }

        return $this->render('forms/handleForms.html.twig', [
            'form' => $form,
            'message' => $message,
        ]);
    }

    #[Route('/admin/category/treatment/delete/{id}', name: 'app_admin_treatment_delete')]
    public function deleteTreatment(EntityManagerInterface $em, int $id)
    {
        $treatment = $em->getRepository(Treatment::class)->find($id);
        $categoryId = $treatment->getCategory()->getId();
        $em->remove($treatment);
        $em->flush();
        $this->addFlash('success', 'Successfully deleted a treatment');

        return $this->redirectToRoute('app_admin_treatments', ['id' => $categoryId]);
    }

    #[Route('/admin/employee', 'app_admin_employee')]
    public function showEmployee(EntityManagerInterface $em)
    {
        $employees = $em->getRepository(User::class)->findBy(['roles' => array('["ROLE_EMPLOYEE"]')]);

        return $this->render('admin/employee.html.twig', [
            'employees' => $employees,
        ]);
    }

    #[Route('/admin/employee/insert', 'app_admin_employee_insert')]
    public function addEmployee(EntityManagerInterface $em, Request $request, UserPasswordHasherInterface $passwordHasher)
    {
        $message = 'Medewerker toevoegen';
        $employee = new User();

        $form = $this->createForm(EmployeeType::class, $employee);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $employee->setRoles(['ROLE_EMPLOYEE']);

            $employee->setPassword($passwordHasher->hashPassword(
                $employee,
                $form->get('password')->getData(),
            ));

            $em->persist($employee);
            $em->flush();
            $this->addFlash('success', 'Successfully added a employee');
            return $this->redirectToRoute('app_admin_employee');
        }

        return $this->render('forms/handleForms.html.twig', [
            'form' => $form,
            'message' => $message,
        ]);
    }

    #[Route('/admin/employee/delete/{id}', 'app_admin_employee_delete')]
    public function deleteEmployee(EntityManagerInterface $em, int $id)
    {
        $employee = $em->getRepository(User::class)->find($id);

        $em->remove($employee);
        $em->flush();
        $this->addFlash('success', 'Successfully deleted a employee');

        return $this->redirectToRoute('app_admin_employee');
    }
}

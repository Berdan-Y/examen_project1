<?php

namespace App\Controller;

use App\Entity\Appointment;
use App\Entity\Category;
use App\Entity\Product;
use App\Entity\Purchase;
use App\Entity\PurchaseLine;
use App\Entity\Treatment;
use App\Form\AppointmentType;
use App\Form\CustomerAppointmentType;
use App\Form\ProductQuantityType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MemberController extends AbstractController
{
    #[Route('/member', name: 'app_member')]
    public function index(EntityManagerInterface $em): Response
    {
        $customer = $this->getUser();
        $appointments = $em->getRepository(Appointment::class)->findBy(['customer' => $customer]);

        return $this->render('member/index.html.twig', [
            'appointments' => $appointments,
        ]);
    }

    #[Route('/member/category', name: 'app_member_category')]
    public function showCategory(EntityManagerInterface $em): Response
    {
        $category = $em->getRepository(Category::class)->findAll();

        return $this->render('member/category.html.twig', [
            'categories' => $category,
        ]);
    }

    #[Route('/member/category/{id}', name: 'app_member_treatments')]
    public function showTreatment(EntityManagerInterface $em): Response
    {
        $treatment = $em->getRepository(Treatment::class)->findAll();

        return $this->render('member/treatment.html.twig', [
            'treatments' => $treatment,
        ]);
    }

    #[Route('/member/appointment/{id}/insert', name: 'app_member_appointment_insert')]
    public function makeAppointment(EntityManagerInterface $em, Request $request, int $id): Response
    {
        $message = 'Maak afspraak';
        $appointment = new Appointment();
        $treatment = $em->getRepository(Treatment::class)->find($id);
        $customer = $this->getUser();

        $form = $this->createForm(CustomerAppointmentType::class, $appointment);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $appointment->setCustomer($customer);
            $appointment->setTreatment($treatment);

            $em->persist($appointment);
            $em->flush();
            $this->addFlash('success', 'Successfully made an appointment');

            return $this->redirectToRoute('app_member');
        }

        return $this->render('forms/handleForms.html.twig', [
            'form' => $form,
            'message' => $message
        ]);
    }

    #[Route('/member/appointment/{id}/update', name: 'app_member_appointment_update')]
    public function changeAppointment(EntityManagerInterface $em, Request $request, int $id): Response
    {
        $message = 'Pas afspraak aan';
        $appointment = $em->getRepository(Appointment::class)->find($id);

        $form = $this->createForm(CustomerAppointmentType::class, $appointment);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($appointment);
            $em->flush();
            $this->addFlash('success', 'Successfully updated an appointment');

            return $this->redirectToRoute('app_member');
        }

        return $this->render('forms/handleForms.html.twig', [
            'form' => $form,
            'message' => $message
        ]);
    }

    #[Route('/member/appointment/{id}/delete', name: 'app_member_appointment_delete')]
    public function cancelAppointment(EntityManagerInterface $em, int $id)
    {
        $appointment = $em->getRepository(Appointment::class)->find($id);

        $em->remove($appointment);
        $em->flush();
        $this->addFlash('success', 'Successfully canceled appointment');

        return $this->redirectToRoute('app_member');
    }

    #[Route('/member/products', 'app_member_products')]
    public function showProducts(EntityManagerInterface $em)
    {
        $products = $em->getRepository(Product::class)->findAll();

        return $this->render('member/products.html.twig', [
            'products' => $products,
        ]);
    }

    #[Route('/member/products/{id}', 'app_member_product_details')]
    public function showProductDetails(EntityManagerInterface $em, Request $request, int $id)
    {
        $productDetails = $em->getRepository(Product::class)->find($id);

        $form = $this->createForm(ProductQuantityType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $session = $request->getSession();

            if (!$session->get('order')) {
                $session->set('order', []);
            }

            $quantity = $form->get('quantity')->getData();
            $order = $session->get('order');
            $order[] = [$id, $quantity];

            $session->set('order', $order);
            $this->addFlash('success', 'Successfully added product to shopping cart');

            return $this->redirectToRoute('app_member_products');
        }

        return $this->render('member/productDetails.html.twig', [
            'form' => $form,
            'product' => $productDetails
        ]);
    }

    #[Route('/member/cart', 'app_member_cart')]
    public function showCart(EntityManagerInterface $em, Request $request)
    {
        $order = $request->getSession()->get('order');

        if ($order === null) {
            $this->addFlash('danger', 'Winkelwagen is leeg');
            return $this->redirectToRoute('app_member_products');
        }

        $purchaseLines = [];

        foreach ($order as $orderLine) {
            $purchaseLine = new PurchaseLine();
            $product = $em->getRepository(Product::class)->find($orderLine[0]);
            $purchaseLine->setProduct($product);
            $purchaseLine->setQuantity($orderLine[1]);
            $purchaseLines[] = $purchaseLine;
        }

        return $this->render('member/cart.html.twig', [
            'purchase_lines' => $purchaseLines
        ]);
    }

    #[Route('/member/cart/order', 'app_member_cart_order')]
    public function orderCart(EntityManagerInterface $em, Request $request)
    {
        $user = $this->getUser();
        $p = $request->getSession()->get('order');

        if (!$p) {
            $this->addFlash('danger', 'Je hebt geen producten');
            return $this->redirectToRoute('app_member_products');
        }

        $purchase = new Purchase();
        $purchase->setCreatedAt(new \DateTimeImmutable('now'));
        $purchase->setUpdatedAt(new \DateTimeImmutable('now'));
        $purchase->setCustomer($user);

        foreach ($p as $line) {
            $orderLine = new PurchaseLine();
            $product = $em->getRepository(Product::class)->find($line[0]);
            $orderLine->setProduct($product);
            $orderLine->setQuantity($line[1]);
            $orderLine->setPurchase($purchase);
            $em->persist($orderLine);
        }
        $em->persist($purchase);
        $em->flush();
        $request->getSession('order')->clear();
        $this->addFlash('success', 'De bestelling is voltooid');
        return $this->redirectToRoute('app_member_products');
    }

    #[Route('/member/cart/clear', 'app_member_cart_clear')]
    public function clearCart(Request $request)
    {
        $request->getSession()->remove('order');
        return $this->redirectToRoute('app_member_products');
    }
}

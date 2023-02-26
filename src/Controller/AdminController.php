<?php

namespace App\Controller;

use App\Entity\Leads;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;

class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin')]
    public function index(EntityManagerInterface $em, Request $request): Response
    {
        $user = $request->query->get('b');
        $from = $request->query->get('from');
        $to = $request->query->get('to');

        $groupFromStatus = $em->getRepository(Leads::class)->getGroupLeads($from, $to, $user);
        $users = [];
        $totalLeads = 0;
        $totalApproveLeads = 0;
        $totalPayout = 0;
        $totalRatePayout = 0;

        foreach ($groupFromStatus as $item) {
            if (!isset($users[$item['telegram']])) {
                $users[$item['telegram']] = [
                    'id' => $item['id'],
                    'rate' => $item['rate'],
                    'payout' => 0,
                    'count' => 0,
                    'approve_leads' => 0
                ];

            }
            $users[$item['telegram']]['payout'] += $item['payout'];
            $users[$item['telegram']]['count'] += $item['leads'];
            if ($item['status'] == 'sale') {
                $users[$item['telegram']]['approve_leads'] += $item['leads'];
                $totalApproveLeads += $item['leads'];
            }
            $totalPayout += $item['payout'];
            $totalRatePayout += $item['payout'] * ($users[$item['telegram']]['rate'] / 100);
            $totalLeads += $item['leads'];

        }

        $totalApproveLeads = $totalLeads != 0 ? round(($totalApproveLeads / $totalLeads) * 100, 1) : 0;
//        dd($totalRatePayout);
        return $this->render('admin/index.html.twig', [
            'users' => $users,
            'total' => [
                'leads' => $totalLeads,
                'payout' => $totalPayout,
                'ratePayout' => $totalRatePayout,
                'approve' => $totalApproveLeads
            ]
        ]);
    }

    #[Route('/change-rate', name: 'app_change_rate')]
    public function changeRate(EntityManagerInterface $em, Request $rq): Response
    {
        if (!$rq->isXmlHttpRequest()) return $this->json(['status' => 'error']);

        $id = $rq->request->get('id');
        $rate = $rq->request->get('rate');

        $user = $em->getRepository(User::class)->find($id);
        if (!$user instanceof User) return $this->json(['status' => 'error']);

        $user->setRate($rate);
        $em->persist($user);
        $em->flush();

        return $this->json(['status' => 'success', 'id' => $id, 'rate' => $rate]);
    }
}

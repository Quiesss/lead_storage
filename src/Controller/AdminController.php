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

        $date = explode(' to ', $request->query->get('ft'));
        $from = new \DateTime();
        $to = new \DateTime();

        if (isset($date['0']) && $date['0'] != "") {
            $from->setTimestamp(strtotime($date['0'] . ' 00:00:00') );
        } else {
            $from->setTimestamp(strtotime('today 00:00:00'));
        }
        if (isset($date['1'])) {
            $to->setTimestamp(strtotime($date['1'] . ' 23:59:59'));
        } else {
            $to->setTimestamp(strtotime($date['0'] . ' 23:59:59') );
        }

        $allUsers = $em->getRepository(User::class)->findAll();
        $usersData = $em->getRepository(User::class)->getLeadFromUser($from, $to, $user);
        $groupFromStatus = $em->getRepository(Leads::class)->getGroupLeads($from, $to, $user);
//        dd($usersData);
        $users = [];

        foreach ($allUsers as $user) {
            $users[$user->getTelegram()] = [
                'telegram' => $user->getTelegram(),
                'id' => $user->getId(),
                'rate' => $user->getRate() ?? 100,
                'count' => 0,
                'approve_leads' => 0,
                'payout' => 0
            ];
        }

        $totalLeads = 0;
        $totalApproveLeads = 0;
        $totalPayout = 0;
        $totalRatePayout = 0;

        foreach ($usersData as $item) {
            if (!isset($users[$item['telegram']])) {
                $users[$item['telegram']] = [
                    'id' => $item['id'],
                    'rate' => $item['rate'] ?? 100,
                    'payout' => 0,
                    'count' => 0,
                    'approve_leads' => 0
                ];

            }

            $users[$item['telegram']]['count'] += $item['leads'];
            if ($item['status'] == 1) {
                $users[$item['telegram']]['payout'] += $item['payout'];
//                $users[$item['telegram']]['ratePayout'] += $item['payout'] * ($users[$item['telegram']]['rate'] / 100);
                $totalPayout += $item['payout'];
                $users[$item['telegram']]['approve_leads'] += $item['leads'];
                $totalApproveLeads += $item['leads'];
            }

            $totalLeads += $item['leads'];

        }

//        dd($users);

        $totalApproveLeads = $totalLeads != 0 ? round(($totalApproveLeads / $totalLeads) * 100, 1) : 0;
//        dd($totalRatePayout);
        return $this->render('admin/index.html.twig', [
            'users' => $users,
            'total' => [
                'leads' => $totalLeads,
                'payout' => $totalPayout,
                'ratePayout' => $totalRatePayout,
                'approve' => $totalApproveLeads
            ],
            'date' => [
                'from' => $from,
                'to' => $to
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

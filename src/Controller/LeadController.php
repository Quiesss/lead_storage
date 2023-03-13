<?php

namespace App\Controller;

use App\Entity\Leads;
use App\Entity\User;
use App\Service\LeadGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Exception;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class LeadController extends AbstractController
{
    #[Route('/', name: 'app_main')]
    public function index(): Response
    {
        return $this->redirectToRoute('app_leads');
    }

    #[Route('/lead/add', name: 'app_lead_add')]
    public function mainRedirect(Request $request, LeadGenerator $lead): Response
    {
        $res = $lead->LeadGenerate($request->query);
        return $this->json($res);
    }

    /**
     * @throws Exception
     */
    #[Route('/leads/{id}', name: 'app_leads', requirements: ['id' => '\d+'], defaults: ['id' => ''])]
    public function getLeads(EntityManagerInterface $em, Request $rq, $id): Response
    {
        if (!empty($id) && $this->isGranted("ROLE_ADMIN")) {
            $user = $em->getRepository(User::class)->find($id);
            if (!$user instanceof User) return $this->redirectToRoute('app_admin');
        } else {
            $user = $this->getUser();
        }

        $date = explode(' to ', $rq->query->get('ft'));
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

        $leads = $em->getRepository(Leads::class)->getLeads(
            $user,
            $from,
            $to
        );

        $totalPayout = 0;
        $totalLeads = count($leads);
        $totalApprove = 0;
        $userRate = $user->getRate() ?? 100;

        foreach ($leads as $lead) {
            $lead->setPayout($lead->getPayout() * ($userRate / 100));
            if ($lead->getStatus() == '1') {
                $totalPayout += round($lead->getPayout() * ($userRate / 100), 1);
                $totalApprove++;
            }
        }
        $totalApprove = $totalLeads != 0 ? round(($totalApprove / $totalLeads) * 100, 1) : 0;
        return $this->render('/lead/index.html.twig', [
            'leads' => $leads,
            'total' => [
                'payout' => round($totalPayout, 1),
                'leads' => $totalLeads,
                'approve' => round($totalApprove, 1),
            ],
            'date' => [
                'from' => $from,
                'to' => $to
            ],
            'user' => $user
        ]);
    }
}

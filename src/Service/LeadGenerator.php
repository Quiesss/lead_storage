<?php

namespace App\Service;

use App\Entity\Leads;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\InputBag;

class LeadGenerator
{

    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function LeadGenerate(InputBag $request)
    {
        $subid = $request->get('subid', 'undefined');
        $createAt = $request->get('createAt');
        $status = $request->get('status', 'undefined');
        $payout = $request->get('payout', 0);
        $from = $request->get('from', '');
        $buyer = $request->get('sub_id_11');
        $geo = $request->get('country', 'undefined');
        $profit = $request->get('profit', );

        $lead = $this->em->getRepository(Leads::class)->findOneBy(['subid' => $subid]);
        if (!$lead instanceof Leads) {
            $lead = new Leads();
        }
        if ($buyer){
            $user = $this->em->getRepository(User::class)->find($buyer);
        } else {
            $user = null;
        }


        $lead->setSubid($subid)
             ->setCreateAt(new DateTimeImmutable($createAt ?? 'now'))
             ->setStatus($status)
             ->setBuyer($user instanceof User ? $user : null)
             ->setGeo($geo)
             ->setPayout($payout)
             ->setProfit($profit ?? null)
             ->setSource($from);

        $this->em->persist($lead);
        $this->em->flush();

        return 1;
    }
}
<?php

namespace App\Command;

use DateTime;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'salary:schedule',
    description: 'Generate a CSV payroll schedule.',
)]
class SalaryScheduleCommand extends Command
{
    private const MONDAY = 1;
    private const TUESDAY = 2;
    private const WEDNESDAY = 3;
    private const THURSDAY = 4;
    private const FRIDAY = 5;
    private const WEEKEND = [6, 7];
    public function __construct()
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addArgument('arg1', InputArgument::OPTIONAL, 'Argument description')
            ->addOption('option1', null, InputOption::VALUE_NONE, 'Option description')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $today = new DateTime();
        $startMonth = (int)$today->format('n'); // month number for iteration
        $year = (int)$today->format('Y');

        $filename = "public/salary_schedule_{$year}.csv";
        $file = fopen($filename, 'w');

        fputcsv($file, ['Month', 'Base Salary Date', 'Bonus Payment Date']);

        for ($month = $startMonth; $month <= 12; $month++) {
            $salaryDate = $this->getSalaryDate($year, $month);
            $bonusDate = $this->getBonusDate($year, $month);

            fputcsv($file, [
                DateTime::createFromFormat('m', $month)->format('F'),
                $salaryDate->format('Y-m-d'),
                $bonusDate->format('Y-m-d'),
            ]);
        }

        fclose($file);

        $io->success("Payment schedule has been written to $filename");

        return Command::SUCCESS;
    }

    private function getSalaryDate(int $year, int $month): DateTime
    {
        $lastDay = (new DateTime("{$year}-{$month}-01"))
            ->modify('last day of this month');

        if (self::fallsOnWeekend($lastDay)) {
            $lastDay->modify('last Friday');
        }

        return $lastDay;
    }

    private function getBonusDate(int $year, int $month): DateTime
    {
        $bonusDay = new DateTime("{$year}-{$month}-15");

        if (self::fallsOnWeekend($bonusDay)) {
            // go to next wednesday
            $bonusDay->modify('next Wednesday');
        }

        return $bonusDay;
    }

    /**
     * @param DateTime $day
     * @return bool
     */
    private function fallsOnWeekend(DateTime $day): bool
    {
        return in_array($day->format('N'), self::WEEKEND);
    }

}

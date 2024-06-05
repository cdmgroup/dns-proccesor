<?php

namespace App\Console\Commands;

use App\Exports\ExportExcel;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

class processFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:process-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // main directory
        $parentDirectory = resource_path('data');

        // final results arr
        $selectedContent = [];

        $directories = scandir($parentDirectory);

        // getting the directories
        foreach ($directories as $directory) {
            if ($directory != '.' && $directory != '..' && $directory != '.DS_Store') {
                $filesPath = $parentDirectory . '/' . $directory;
                if (is_dir($filesPath)) {
                    $files = scandir($filesPath);
                    foreach ($files as $file) {
                        $domainName = str_replace(".txt", "", $file);
                        if ($file != "." && $file != ".." && !is_dir($directory . DIRECTORY_SEPARATOR . $file)) {
                            $content = file_get_contents($filesPath . DIRECTORY_SEPARATOR . $file);
                            $splitContent = explode(PHP_EOL, $content);
                            if (pathinfo($file, PATHINFO_EXTENSION) === 'txt') {
                                foreach ($splitContent as $word) {
                                    $word = preg_replace('/\t/', ' ', $word);
                                    $word = preg_replace('/\r/', ' ', $word);
                                    if (str_contains($word, $domainName)) {
                                        if (str_contains($word, 'IN A') || str_contains($word, 'IN CNAME') || str_contains($word, 'IN APEXALIAS')) {
                                            $splitWord = explode('IN ', $word);
                                            $word = $splitWord[1];
                                            if (str_contains($word, $domainName)) {
                                                array_push($selectedContent, [
                                                    'domainName' => $domainName,
                                                    'content' => trim($word),
                                                    'zone' => $directory
                                                ]);
                                            }
                                        }
                                    } else {
                                        if(str_contains($word, 'IN A') && !str_contains($word, '@') && !str_contains($word, 'www') && !str_contains($word, '*')) {
                                            $splitWord = explode(' ', $word);
                                            array_push($selectedContent, [
                                                'domainName' => $domainName,
                                                'content' => trim('A '. $splitWord[0] . '.' . $domainName),
                                                'zone' => $directory
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                    }

                }
            }
        }
        $data = $this->processData($selectedContent);
        Excel::store(new ExportExcel($data), 'dns-unified.xlsx');

        $this->info('Files Content selected and saved success on excel file.');
    }

    public function processData($data) {
        $dataProcessed = [];
        $count = 0;
        if (count($data) > 0) {
            foreach($data as $arrLine) {
                array_push($dataProcessed, $this->processLine($arrLine));
                $count++;
            }
        }
        return $dataProcessed;
    }

    public function processLine($line) {
        $dataFormatted = [
            'A' => '',
            'CNAME' => '',
            'APEX' => '',
            'ZONE' => $line['zone'],
            'TXT NAME' => $line['domainName']
        ];
        $a = [];
        $cname = [];
        $apex = [];
        $splitLine = explode(' ', $line['content']);
        switch($splitLine[0]) {
            case 'A':
                array_push($a, $splitLine[1]);
            break;
            case 'CNAME':
                array_push($cname, $splitLine[1]);
            break;
            case 'APEXALIAS':
                array_push($apex, $splitLine[1]);
            break;
        }
        $dataFormatted['A'] = implode(PHP_EOL, $a);
        $dataFormatted['CNAME'] = implode(PHP_EOL, $cname);
        $dataFormatted['APEX'] = implode(PHP_EOL, $apex);

        return $dataFormatted;
    }
}

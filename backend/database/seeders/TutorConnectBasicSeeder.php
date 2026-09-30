<?php

namespace Database\Seeders;

use App\Models\PaymentMethod;
use App\Models\StudyProgram;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class TutorConnectBasicSeeder extends Seeder
{
    public function run(): void
    {
        $studyPrograms = [
            ['id' => 1, 'name' => 'Angewandte Informatik (KIA/KIS) B.Sc.', 'description' => null],
            ['id' => 3, 'name' => 'Architektur B.Sc.', 'description' => null],
            ['id' => 4, 'name' => 'Bauingenieurwesen ausbildungsbegleitend B.Sc.', 'description' => null],
            ['id' => 5, 'name' => 'Bauingenieurwesen B.Sc.', 'description' => null],
            ['id' => 6, 'name' => 'Betriebswirtschaftslehre B.A.', 'description' => null],
            ['id' => 7, 'name' => 'Betriebswirtschaftslehre B.A. (Verbund)', 'description' => null],
            ['id' => 8, 'name' => 'Clinical Research Management B.Sc.', 'description' => null],
            ['id' => 9, 'name' => 'Elektrotechnik (KIA) B.Sc.', 'description' => null],
            ['id' => 10, 'name' => 'Elektrotechnik B.Sc.', 'description' => null],
            ['id' => 11, 'name' => 'Ergotherapie B.Sc.', 'description' => null],
            ['id' => 12, 'name' => 'Ergotherapie weiterqualifizierend B.Sc.', 'description' => null],
            ['id' => 13, 'name' => 'Gesundheitsbezogene Soziale Arbeit B.A.', 'description' => null],
            ['id' => 14, 'name' => 'Gesundheitsökonomie B.Sc.', 'description' => null],
            ['id' => 15, 'name' => 'Gesundheitspsychologie B.Sc.', 'description' => null],
            ['id' => 16, 'name' => 'Gesundheitswissenschaften B.Sc.', 'description' => null],
            ['id' => 17, 'name' => 'Hebammenwissenschaft B.Sc.', 'description' => null],
            ['id' => 18, 'name' => 'Informatik (KIA) B.Sc.', 'description' => null],
            ['id' => 19, 'name' => 'Informatik B.Sc.', 'description' => null],
            ['id' => 20, 'name' => 'International Business and Management B.A.', 'description' => null],
            ['id' => 21, 'name' => 'Logopädie B.Sc.', 'description' => null],
            ['id' => 22, 'name' => 'Logopädie weiterqualifizierend B.Sc.', 'description' => null],
            ['id' => 23, 'name' => 'Management nachhaltiger Innovationen im Gesundheitswesen B.Sc.', 'description' => null],
            ['id' => 24, 'name' => 'Maschinenbau (KIA) B.Eng.', 'description' => null],
            ['id' => 25, 'name' => 'Maschinenbau B.Eng.', 'description' => null],
            ['id' => 26, 'name' => 'Mechatronik (KIA) B.Eng.', 'description' => null],
            ['id' => 27, 'name' => 'Mechatronik B.Eng.', 'description' => null],
            ['id' => 28, 'name' => 'Mechatronische Systeme (KIA / KIS) B.Sc.', 'description' => null],
            ['id' => 29, 'name' => 'Mechatronische Systeme B.Sc.', 'description' => null],
            ['id' => 30, 'name' => 'Nachhaltige Entwicklung B.Sc.', 'description' => null],
            ['id' => 31, 'name' => 'Nachhaltiges Management in der Gesundheitswirtschaft B.Sc.', 'description' => null],
            ['id' => 32, 'name' => 'Pflege B.Sc.', 'description' => null],
            ['id' => 33, 'name' => 'Pflegewissenschaft B.Sc.', 'description' => null],
            ['id' => 34, 'name' => 'Physiotherapie B.Sc.', 'description' => null],
            ['id' => 35, 'name' => 'Physiotherapie weiterqualifizierend B.Sc.', 'description' => null],
            ['id' => 36, 'name' => 'Regenerative Energiesysteme B.Sc.', 'description' => null],
            ['id' => 37, 'name' => 'Umweltinformatik ausbildungsbegleitend B.Sc.', 'description' => null],
            ['id' => 38, 'name' => 'Umweltinformatik B.Sc.', 'description' => null],
            ['id' => 39, 'name' => 'Umweltingenieurwesen B.Sc.', 'description' => null],
            ['id' => 40, 'name' => 'Vermessung (KIA) B.Eng.', 'description' => null],
            ['id' => 41, 'name' => 'Vermessung B.Eng.', 'description' => null],
            ['id' => 42, 'name' => 'Wirtschafts- und Industrieinformatik (KIS) B.Sc.', 'description' => null],
            ['id' => 43, 'name' => 'Wirtschafts- und Industrieinformatik B.Sc.', 'description' => null],
            ['id' => 44, 'name' => 'Wirtschaftsinformatik B.Sc.', 'description' => null],
            ['id' => 45, 'name' => 'Wirtschaftsingenieurwesen B.Sc. (Verbund)', 'description' => null],
            ['id' => 46, 'name' => 'Wirtschaftsingenieurwesen Bau B.Sc.', 'description' => null],
            ['id' => 47, 'name' => 'Wirtschaftsingenieurwesen Elektrotechnik B.Sc.', 'description' => null],
            ['id' => 48, 'name' => 'Wirtschaftsingenieurwesen Maschinenbau B.Sc.', 'description' => null],
        ];

        foreach ($studyPrograms as $studyProgram) {
            StudyProgram::updateOrCreate(
                ['id' => $studyProgram['id']],
                [
                    'name' => $studyProgram['name'],
                    'description' => $studyProgram['description'],
                ]
            );
        }

        $subjects = [
            ['id' => 1, 'study_program_id' => 1, 'name' => 'Algorithmen und Datenstrukturen in C/C++', 'description' => null],
            ['id' => 2, 'study_program_id' => 1, 'name' => 'Analysis 1', 'description' => null],
            ['id' => 3, 'study_program_id' => 1, 'name' => 'Analysis 2', 'description' => null],
            ['id' => 4, 'study_program_id' => 1, 'name' => 'Angewandte CAE', 'description' => null],
            ['id' => 5, 'study_program_id' => 1, 'name' => 'Anwendungsentwurf und -entwicklung', 'description' => null],
            ['id' => 6, 'study_program_id' => 1, 'name' => 'Automatisiertes Fahren', 'description' => null],
            ['id' => 7, 'study_program_id' => 1, 'name' => 'Betriebliche Informationssysteme', 'description' => null],
            ['id' => 8, 'study_program_id' => 1, 'name' => 'Betriebsorganisation & Produktionsmanagement', 'description' => null],
            ['id' => 9, 'study_program_id' => 1, 'name' => 'Controlling', 'description' => null],
            ['id' => 10, 'study_program_id' => 1, 'name' => 'Datenanalyse und Datenvisualisierung', 'description' => null],
            ['id' => 11, 'study_program_id' => 1, 'name' => 'Datenbanken und Datensicherheit', 'description' => null],
            ['id' => 12, 'study_program_id' => 1, 'name' => 'Digitale Methoden zum kollaborativen Arbeiten & Präsentieren', 'description' => null],
            ['id' => 13, 'study_program_id' => 1, 'name' => 'Digitale Werkzeuge in Ingenieurwissenschaft und Informatik', 'description' => null],
            ['id' => 14, 'study_program_id' => 1, 'name' => 'Eingebettete Systeme', 'description' => null],
            ['id' => 15, 'study_program_id' => 1, 'name' => 'Elektrische Antriebe', 'description' => null],
            ['id' => 16, 'study_program_id' => 1, 'name' => 'Elektronische Bauelemente und Schaltungen', 'description' => null],
            ['id' => 17, 'study_program_id' => 1, 'name' => 'Elektrotechnik 1', 'description' => null],
            ['id' => 18, 'study_program_id' => 1, 'name' => 'Elektrotechnik 2', 'description' => null],
            ['id' => 19, 'study_program_id' => 1, 'name' => 'Elektrotechnische Grundlagen für Informatiker', 'description' => null],
            ['id' => 20, 'study_program_id' => 1, 'name' => 'Experimentelle Methoden', 'description' => null],
            ['id' => 21, 'study_program_id' => 1, 'name' => 'Führung und Veränderung', 'description' => null],
            ['id' => 22, 'study_program_id' => 1, 'name' => 'Gesellschaft, Verantwortung & Engagement', 'description' => null],
            ['id' => 23, 'study_program_id' => 1, 'name' => 'Gründungsprojekt', 'description' => null],
            ['id' => 24, 'study_program_id' => 1, 'name' => 'Grundlagen der Automatisierungstechnik', 'description' => null],
            ['id' => 25, 'study_program_id' => 1, 'name' => 'Grundlagen der Betriebswirtschaftslehre', 'description' => null],
            ['id' => 26, 'study_program_id' => 1, 'name' => 'Grundlagen CAE', 'description' => null],
            ['id' => 27, 'study_program_id' => 1, 'name' => 'Grundlagen der Fahrzeugtechnik', 'description' => null],
            ['id' => 28, 'study_program_id' => 1, 'name' => 'Grundlagen Informatik', 'description' => null],
            ['id' => 29, 'study_program_id' => 1, 'name' => 'Grundlagen der Nachhaltigkeit', 'description' => null],
            ['id' => 30, 'study_program_id' => 1, 'name' => 'Grundlagen der Regelungstechnik', 'description' => null],
            ['id' => 31, 'study_program_id' => 1, 'name' => 'Grundlagen Robotik', 'description' => null],
            ['id' => 32, 'study_program_id' => 1, 'name' => 'Hardwarenahe Programmierung', 'description' => null],
            ['id' => 33, 'study_program_id' => 1, 'name' => 'Ingenieurpädagogische Ausbildung - Wahlfach', 'description' => null],
            ['id' => 34, 'study_program_id' => 1, 'name' => 'Internet der Dinge', 'description' => null],
            ['id' => 35, 'study_program_id' => 1, 'name' => 'IT-Infrastrukturen', 'description' => null],
            ['id' => 36, 'study_program_id' => 1, 'name' => 'IT-Servicemanagement', 'description' => null],
        ];

        foreach ($subjects as $subject) {
            Subject::updateOrCreate(
                ['id' => $subject['id']],
                [
                    'study_program_id' => $subject['study_program_id'],
                    'name' => $subject['name'],
                    'description' => $subject['description'],
                ]
            );
        }

        $paymentMethods = [
            ['id' => 1, 'name' => 'Bar'],
            ['id' => 2, 'name' => 'PayPal'],
            ['id' => 3, 'name' => 'Tausch'],
            ['id' => 4, 'name' => 'Kostenlos'],
        ];

        foreach ($paymentMethods as $paymentMethod) {
            PaymentMethod::updateOrCreate(
                ['id' => $paymentMethod['id']],
                ['name' => $paymentMethod['name']]
            );
        }
    }
}

<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class RowCollectionImport implements ToCollection, WithHeadingRow
{
    public function collection(Collection $collection)
    {
        return $collection;
    }

    // public function headingRow()
    // {
    //     return [
    //         [
    //             ['type', 'text', 'options', 'correct_answer', 'difficulty', 'points'],
    //             ['mcq', 'What is the capital of France?', '["Paris","London","Berlin","Rome"]', 'Paris', 'easy', 2],
    //             ['true_false', 'The Earth revolves around the Sun.', '["True","False"]', true, 'easy', 1],
    //             ['essay', 'Explain the principles of object-oriented programming.', null, null, 'hard', 10],
    //             ['short_answer', 'Who developed the theory of relativity?', null, 'Albert Einstein', 'medium', 3],
    //             ['mcq', 'Which language runs in the browser?', '["JavaScript","Python","Java","C#"]', 'JavaScript', 'easy', 2],
    //             ['mcq', null, '["A","B","C","D"]', 'A', 'easy', 2], ['mcq', 'Which planet is known as the Red Planet?', 'not a json', 'Mars', 'easy', 2], ['mcq', 'Which number is even?', '["1","2","3","5"]', 4, 'easy', 2], ['unknown', 'This question has an invalid type.', '["Yes","No"]', 'Yes', 'easy', 1], ['true_false', "Water boils at 100\u00b0C at sea level.", '["True","False"]', 'Maybe', 'easy', 1], ['mcq', 'Which HTTP method updates a resource?', '["GET","POST","PUT","DELETE"]', 'PUT', 'expert', 2], ['short_answer', 'What is the binary representation of decimal 5?', null, 101, -1, 2],
    //             ['essay', 'Describe the CAP theorem.', null, null, 'medium', -5],
    //             ['mcq', 'Laravel is written in which language?', '["PHP","JavaScript","Python","Go"]', 'PHP', 'medium', 2],
    //             ['true_false', 'JSON stands for JavaScript Object Notation.', '["True","False"]', true, 'easy', 1],
    //         ],
    //     ];
    // }
}

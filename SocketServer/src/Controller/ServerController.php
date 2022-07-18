<?php

namespace MyApp\Controller;
use MyApp\Utilities\Message_Handler;
use \Ratchet\ConnectionInterface;
class ServerController
{
    private array $students = array();
    private array $professors = array();
    private bool $isEncrypted = false;
    public function __construct()
    {
    }

    public function handleMsg(ConnectionInterface $origin_connection, $msg)
    {
        var_dump($msg);
        echo "\nreciveing message \n";
        $msg_obj = json_decode($msg, true);
        //TODO validate the message more correctly
        //TODO handle exception
        if ($this->isEncrypted)
        {
            //check if it has previous connection
            $person = $this->getPersonByConnection(array_merge($this->professors, $this->students), $origin_connection);
            if(!is_object($person))
            {
                $plain_repsonse = (Professor::DENIED_ACCESS);
                $response = ['action'=> $plain_repsonse[0], 'from'=>$plain_repsonse[1],'to'=>'unknown',
                    'execute' => $plain_repsonse[3]
                ];
                $origin_connection->send(Message_Handler::encode_msg($response));
                return;

            }
            //check if it is professor

            return;
        }
        if (!$msg_obj)
        {
            echo "invaild message";//should through error
            $response = ['response', 'server', 'unknown', '00000', ['status'=>'BAD_REQUEST']];
            $this->sendToConnection($origin_connection,...$response);
            return;
        }
        if ($msg_obj['action'] == 'connect')
        {

            $this->connect($origin_connection, $msg_obj);

        }
        //if the message is not plain object, it will be encrypted
        if($msg_obj['action'] != 'connect')
        {
            $action_array = $msg_obj;

            $person_type = $action_array['from'];
            $person = $this->getPersonByConnection(array_merge($this->professors, $this->students), $origin_connection);
//            echo $professor . "professor is\n";
            if(!is_object($person))
            {
                $plain_repsonse = (Professor::DENIED_ACCESS);
                $response = ['action'=> $plain_repsonse[0], 'from'=>$plain_repsonse[1],'to'=>$person_type,
                'execute' => $plain_repsonse[3]
                ];
                $origin_connection->send(Message_Handler::encode_msg($response));
                return;

            }
            echo "person has valid connection\n";
            if($person instanceof Professor)
            {
//                $action_array = Message_Handler::decrypt_msg($msg, $professor->getToken());
                $this->handle_professor_command($person, $action_array);
            } elseif ($person instanceof Student)
            {
                $this->handle_student_command($person, $action_array);
            }


        }
//        if($msg_obj['to'] == 'student')
//            $this->order_student($msg_obj['device_id'], $msg_obj['action'], $msg_obj['execute'], $msg_obj['from']);


    }
/*
 * connect:
 * we need to check duplicate first
 */
    private function connect(ConnectionInterface $from, $msg_obj)
    {

        if($msg_obj["from"] == 'student')
        {
            //create new student
            if($this->isDuplicate($this->students, $from, $msg_obj['device_id']))
            {
                $plain_repsonse = (['response', 'server', 'student',$msg_obj['device_id'],['status'=> 'DUPLICATE_CONNECTION']]);
                $this->sendToConnection($from, ...$plain_repsonse);
                return;

            }
            $this->students[] = new Student($from,"",$msg_obj['device_id'], $msg_obj['execute']['name']);
        }
        if ($msg_obj['from'] == 'professor')
        {
            if($this->isDuplicate($this->professors, $from, $msg_obj['device_id']))
            {
                $plain_repsonse = (['response', 'server', 'professor',$msg_obj['device_id'],['status'=> 'DUPLICATE_CONNECTION']]);

                $this->sendToConnection($from, ...$plain_repsonse);
                return;

            }
            $this->professors[] = new Professor($from, $msg_obj['execute']['token'], $msg_obj['device_id'], $msg_obj['execute']['name']);
            //TODO throw error
        }
    }
    private function handle_professor_command($professor, $prof_command): void
    {
        var_dump($prof_command);
        switch ($prof_command['action'])
        {
            case 'getStudents':
                $student_names = array();
                echo "number of unverified stduents". count($this->students) . "\n";
                foreach ($this->students as $student)
                {
                    echo $student->get_name() . "\n";
                    $student_names[] = $student->get_name();

                }
                $professor->send_to('responseStudents', 'server', ['studentNames' => $student_names]);

                break;
            case 'verifyStudents':
                if(!$this->orderStudents('open cam for prof', 'professor', array(), $prof_command['device_id']))
                    $professor->send_to(...Professor::BAD_REQUEST);
                break;
            case 'startExam':
                if(!$this->orderStudents('startExam', 'professor', $prof_command['execute'], $prof_command['device_id']))
                    $professor->send_to(...Professor::BAD_REQUEST);
                break;
            case 'sendFile':
                if(!$this->orderStudents('sendFile', 'professor', $prof_command['execute'], $prof_command['device_id']))
                    $professor->send_to(...Professor::BAD_REQUEST);
                break;
            default:
                echo 'invalid action\n';
                $professor->send_to('response', 'server', ['status'=>'BAD_REQUEST']);

        }
    }
    private function handle_student_command(Student $student, array $student_command)
    {
        switch ($student_command['action'])
        {
            default:
                echo 'bad request for student'. "\n";
                $student->send_to('response', 'server', ['status'=>'BAD_REQUEST']);
        }
    }
    private function orderStudents($action, $origin, $execute, $id): bool
    {
        if($id == '00000')
        {
            echo "sending to all student\n";
            foreach ( $this->students as $student)
            {
                $student->send_to($action, $origin, $execute);
            }
            return true;

        }
        $student = $this->getPersonByDeviceId($this->students, $id);
        if (!is_object($student))
        {
            return false;
        }
        $student->send_to($action, $origin, $execute);
        return true;
    }

    private function getPersonByConnection(array $persons, ConnectionInterface $origin_connection)
    {
        foreach ($persons as  $person)
        {
            if($origin_connection == $person->getConnectionInterface())
            {
                return $person;
            }
        }
        return false;
    }
    private function getPersonByDeviceId($persons, $id)
    {
        foreach ($persons as $person)
        {
            if($person->getDeviceId == $id)
                return $person;
        }
        return false;
    }
    private function isDuplicate($persons, $origin_connection, $id): bool
    {
        foreach ($persons as  $person)
        {
            if($origin_connection == $person->getConnectionInterface() || $id == $person->getId())
            {
                return true;
            }
        }
        return false;
    }

    /**
     * @param ConnectionInterface $from
     * @return void
     */
    private function sendToConnection(ConnectionInterface $from, $action, $origin, $destination, $device_id,$execute): void
    {
        $response = ['action' => $action, 'to' => $destination, 'from' => $origin, 'device_id' => $device_id,
            'execute' => $execute
        ];
        $from->send(Message_Handler::encode_msg($response));
    }




}
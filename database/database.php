<?php

include_once __DIR__."/singleton.php";
class database
{
    use singleton;
    public $db=null;
    private function __construct()
    {
        try {
        $this->db = new PDO('sqlite:'.ROOT.'database/school.sqlite');
        } catch (PDOException $e){
            Sentry\captureException($e);
            die($e->getMessage());
        }
    }
    public function reinitDb()
    {
        try {
            $this->db->exec("CREATE TABLE 
                                            classes_groups(
                                                id INTEGER PRIMARY KEY AUTOINCREMENT, 
                                                name TEXT,
                                                val INTEGER
                                            );");
            $this->db->exec("INSERT INTO classes_groups(id, name,val) values (1,'1 класс',1),(2,'2 класс',2),(3,'3 класс',3),(4,'4 класс',4);");

            $this->db->exec('CREATE TABLE groups(id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT,group_id INTEGER,FOREIGN KEY (group_id) REFERENCES classes_groups(val));');
            $this->db->exec("INSERT INTO groups(id,name,group_id) values (1,'1 класс',1),(2,'2 класс',2),(3,'3 класс А',3),(4,'3 класс О',3),(5,'4 класс',4);");

            $this->db->exec('CREATE TABLE users(id INTEGER PRIMARY KEY AUTOINCREMENT,name TEXT,type INTEGER);');
            $this->db->exec("INSERT INTO users(id,name,type) values (1,'Администратор',1),(2,'Васильев В.В.',2),(3,'Петров А.Ю.',2),(4,'Сереньтьев В.Г.',3),(5,'Мужичок К.У.',3);");

            $this->db->exec("CREATE TABLE 
                                            subjects(
                                                id INTEGER PRIMARY KEY AUTOINCREMENT, 
                                                name TEXT, 
                                                active INTEGER, 
                                                teacher_id INTEGER NOT NULL, 
                                                group_id INTEGER NOT NULL,
                                                FOREIGN KEY (teacher_id) REFERENCES users(id)
                                                FOREIGN KEY (group_id) REFERENCES classes_groups(val)
                                            );");
            $this->db->exec("INSERT INTO subjects(id,name,active,teacher_id,group_id) values
                                                    (1,'Пение',1,2,1),
                                                    (2,'Английский',1,2,1),
                                                    (3,'Математика',1,3,1),
                                                    (4,'Физкультура',1,3,1),
                                                    (5,'Природоведение',1,2,1)
                                                    ;");

            $this->db->exec("CREATE TABLE 
                                            quarters(
                                                id INTEGER PRIMARY KEY AUTOINCREMENT, 
                                                name TEXT, 
                                                now TEXT, 
                                                year INTEGER,
                                                start1 DATE,
                                                finish1 DATE,
                                                start2 DATE,
                                                finish2 DATE,
                                                start3 DATE,
                                                finish3 DATE,
                                                start4 DATE,
                                                finish4 DATE
                                            );");
            $this->db->exec("INSERT INTO quarters(id,name,now, year, start1,finish1,start2,finish2,start3,finish3,start4,finish4) values
                                                    (1,'2019-2020','Y','2019','2019-09-02','2019-10-31','2019-11-11','2019-12-24','2020-01-13','2020-02-28','2020-04-06','2020-05-30')
                                                    ;");

            $this->db->exec("CREATE TABLE 
                                            students_groups(
                                                id INTEGER PRIMARY KEY AUTOINCREMENT, 
                                                student_id INTEGER,
                                                classes_id INTEGER,
                                                FOREIGN KEY (student_id) REFERENCES users(id),
                                                FOREIGN KEY (classes_id) REFERENCES groups(id)
                                            );");
            $this->db->exec("INSERT INTO students_groups (student_id,classes_id) values (4,1),(5,1);");

            $this->db->exec("CREATE TABLE 
                                            journal(
                                                id INTEGER PRIMARY KEY AUTOINCREMENT, 
                                                subject INTEGER,
                                                group_id INTEGER,
                                                date_int INTEGER,
                                                date DATE,
                                                teacher INTEGER,
                                                name TEXT,
                                                theme TEXT,
                                                comment TEXT,
                                                FOREIGN KEY (subject) REFERENCES subjects(id),
                                                FOREIGN KEY (group_id) REFERENCES groups(id),
                                                FOREIGN KEY (teacher) REFERENCES users(id)
                                            );");

            $this->db->exec("CREATE TABLE 
                                            type_marks(
                                                id INTEGER PRIMARY KEY AUTOINCREMENT, 
                                                full TEXT,
                                                short TEXT
                                            );");
            $this->db->exec("INSERT INTO type_marks (short,full) values
                                                                ('кр','контрольная работа'),
                                                                ('дз','домашняя работа'),
                                                                ('ср','самостоятельная работа'),
                                                                ('аудир.','аудирование (Английский язык)'),
                                                                ('консп.','конспект');");

            $this->db->exec("CREATE TABLE
                                            marks(
                                                id INTEGER PRIMARY KEY AUTOINCREMENT,
                                                subject_id INTEGER,
                                                journal_id INTEGER,
                                                student_id INTEGER,
                                                type_id INTEGER,
                                                mark TEXT,
                                                date_create INTEGER,
                                                FOREIGN KEY (subject_id) REFERENCES subjects(id),
                                                FOREIGN KEY (type_id) REFERENCES type_marks(id),
                                                FOREIGN KEY (student_id) REFERENCES users(id),
                                                FOREIGN KEY (journal_id) REFERENCES journal(id)
                                            );");
            if ($this->db->errorInfo()[0]!='00000')
                var_dump($this->db->errorInfo());
        } catch (PDOException $e){
            Sentry\captureException($e);
            die($e->getMessage());
        }
    }
    public function query($q)
    {
        try {
            $res = $this->db->query($q);
            if ($res===false) throw new PDOException('ошибка запроса');
            $res = $res->fetchAll();
        } catch (PDOException $e){
            Sentry\captureException($e);
            var_dump($this->db->errorInfo());
            die($e->getMessage());
        }
        return $res;
    }
}

<?php

class Model
{
    protected $pdo;

    public function __construct(array $config)
    {
        try {
            if ($config['engine'] == 'mysql') {
                $this->pdo = new \PDO(
                    'mysql:dbname='.$config['database'].';host='.$config['host'],
                    $config['user'],
                    $config['password']
                );
                $this->pdo->exec('SET CHARSET UTF8');
            } else {
                $this->pdo = new \PDO(
                    'sqlite:'.$config['file']
                );
            }
        } catch (\PDOException $error) {
            throw new ModelException('Unable to connect to database');
        }
    }

    /**
     * Tries to execute a statement, throw an explicit exception on failure
     */
    protected function execute(\PDOStatement $query, array $variables = array())
    {
        if (!$query->execute($variables)) {
            $errors = $query->errorInfo();
            throw new ModelException($errors[2]);
        }

        return $query;
    }

    /**
     * Inserting a book in the database
     */
    public function insertBook($title, $author, $synopsis, $image, $copies)
    {
        $query = $this->pdo->prepare('INSERT INTO livres (titre, auteur, synopsis, image)
            VALUES (?, ?, ?, ?)');
        $this->execute($query, array($title, $author, $synopsis, $image));


        $idBook = $this->pdo->lastInsertId();
        $query = $this->pdo->prepare('INSERT INTO exemplaires (book_id) VALUES (?)');

        for($i = 0; $i < $copies; $i++)
        {
          $this->execute($query, array($idBook));
        }

    }

    /**
     * Getting all the books
     */
    public function getBooks()
    {
        $query = $this->pdo->prepare('SELECT livres.* FROM livres');

        $this->execute($query);

        return $query->fetchAll();
    }

    /**
     * Getting a book
     */
     public function getBook($id)
     {
       $query = $this->pdo->prepare(
         'SELECT livres.*
          FROM livres
          WHERE livres.id = ?');

       $this->execute($query, array($id));

       return $query->fetch();
     }


     /**
      * Getting all copies of a book
      */
      public function getCopies($id)
      {
        $query = $this->pdo->prepare('SELECT exemplaires.* FROM exemplaires WHERE exemplaires.book_id = ?');

        $this->execute($query, array($id));

        return $query->fetchAll();
      }


      /**
       * Getting all copies of a book
       */
       public function getCopiesAvailable($id)
       {
         $query = $this->pdo->prepare('SELECT exemplaires.* FROM exemplaires WHERE exemplaires.book_id = ?
           AND exemplaires.book_id NOT IN (SELECT emprunts.* FROM emprunts WHERE emprunts.exemplaire = ?)');

         $this->execute($query, array($id, $id));

         return $query->fetchAll();
       }
}

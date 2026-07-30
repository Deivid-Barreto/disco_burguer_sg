<?php
//session_start();
//include 'conexao.php';

//if($_SERVER['REQUEST_METHOD'] == 'POST'){

    // Executa uma consulta SQL para buscar um usuário com o e-mail, senha e cargo enviados pelo formulário
    //$res = $conn->query("
        //SELECT * FROM autenticacao
        //WHERE email='{$_POST['email']}'
        //AND senha='{$_POST['senha']}'
        //AND cargo='{$_POST['cargo']}'
    //");

    //$user=$res->fetch_assoc();

    // Se o usuário foi encontrado (se a variável $user não estiver vazia)
    //if($user){

    // Salva os dados do usuário encontrado na sessão global para mantê-lo logado
    //  $_SESSION['user']=$user; 

    // Verifica o nível de acesso do usuário. Se for administrador:
        //if($user['tipo']=='admin') {

            // Redireciona para a página administrativa 'inicio.html'
          //  header("Location: inicio.html");
              //exit();

        //}elseif($user['tipo']=='gar'){

          //  header("Location: mesa.html");

        //}elseif ({$user['tipo']=='coz')

        //    header("Location: cozinheiro.html");

        //}else{
        //echo "E-mail, senha ou cargo inválidos.";
        //}
    //}
//}
?>

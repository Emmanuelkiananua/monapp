<?php

declare(strict_types=1);

namespace App\Web\Controllers;

use App\Models\User;
use App\DTO\RegisterDTO;
use App\Models\Job;
use Core\Controller;
use Core\Request;
use Core\ValidationException;

final class UserController extends Controller
{
    public function index(Request $request): void
    {
        $users = User::GetUserAndJob();

        $this->view('users/index.twig', [
            'users' => $users
        ]);
    }

    public function create(Request $request): void
    {

      $jobs = Job::all();
        $this->view('users/add.twig', [
            'action' => $this->view->url('/users'),
            'jobs'=>$jobs
        ]);
    }

    public function store(Request $request): void
    {
        try {
            $input = RegisterDTO::fromArray($request->all());
        } catch (ValidationException $e) {
            $this->session->flash('errors', $e->errors);
            $this->session->flash('old', $request->all());
            $this->redirect('/adduser');
            return;
        }

        // Vérification de l'adresse email
        $emailExists = User::table()
            ->where('email', '=', $input->email)
            ->first();

        if ($emailExists !== null) {
            $this->session->flash('error', 'Cet email est déjà utilisé.');
            $this->session->flash('old', $request->all());
            $this->redirect('/adduser');
            return;
        }

        // Vérification du nom d'utilisateur
        $usernameExists = User::table()
            ->where('username', '=', $input->username)
            ->first();

        if ($usernameExists !== null) {
            $this->session->flash('error', "Ce nom d'utilisateur est déjà utilisé.");
            $this->session->flash('old', $request->all());
            $this->redirect('/adduser');
            return;
        }

        // Création de l'utilisateur
        User::insert([
            'username' => $input->username,
            'email' => $input->email,
            'password_hash' => password_hash($input->password, PASSWORD_DEFAULT),
            'role' => $input->role,
            'job_id' => $input->job,
        ]);

        $this->session->flash('success', 'Compte créé, vous êtes connecté.');
        $this->redirect('/users');
    }

    public function remove(Request $request): void
    {
        $user = $request->all();

        $id_user = (int) $user['id_user'];

        // Vérification de l'existence de l'utilisateur
        if (User::find($id_user) === null) {
            $this->session->flash('error', 'Utilisateur introuvable.');
            $this->redirect('/users');
            return;
        }

        // Suppression de l'utilisateur
        User::delete($id_user);

        $this->session->flash('success', 'Utilisateur supprimé.');
        $this->redirect('/users');
    }
}

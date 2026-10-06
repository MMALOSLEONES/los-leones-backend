<?php

namespace App\Services;

use App\Models\Article;

class ArticleService
{
    public function recupererTout()
    {
        // En entreprise, on trie souvent selon l'index de performance configuré en BD (status + date)
        return Article::orderBy('date_publication', 'desc')->get();
    }

    public function trouverParId(string $id): Article
    {
        return Article::findOrFail($id);
    }

    public function creer(array $donnees): Article
    {
        return Article::create($donnees);
    }

    public function mettreAJour(string $id, array $donnees): Article
    {
        $article = $this->trouverParId($id);
        $article->update($donnees);
        return $article;
    }

    public function supprimer(string $id): bool
    {
        $article = $this->trouverParId($id);
        return $article->delete();
    }
}

<?php

namespace App\Service;

use App\Entity\Admin\Etablissement;
use App\Entity\Webapp\Article;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Calcule où doivent vivre les médias uploadés (logos/bandeaux d'établissement,
 * images et pièces jointes d'articles), rangés par propriétaire (établissement,
 * ou administrateur auteur si l'article n'est lié à aucun établissement) puis
 * par type de média.
 *
 * Les méthodes `*Dir()` renvoient un chemin disque absolu et créent le dossier
 * s'il n'existe pas encore (utilisées par les contrôleurs avant move()/unlink()).
 * Les méthodes `*Url()` renvoient un chemin public relatif, sans accès disque
 * (utilisées par l'extension Twig, à envelopper dans asset() côté template).
 */
class MediaPathResolver
{
    private const SUPPORT_AUDIO = 1;
    private const SUPPORT_VIDEO = 2;
    private const SUPPORT_DOCUMENT = 3;

    private const AUDIO_EXTENSIONS = ['mp3', 'wav'];
    private const VIDEO_EXTENSIONS = ['mp4', 'mpeg', 'mpg'];
    private const DOCUMENT_EXTENSIONS = ['pdf', 'doc', 'docx'];

    public function __construct(
        private readonly string $etablissementsBaseDir,
        private readonly string $adminsBaseDir,
        private readonly Filesystem $filesystem,
        private readonly SluggerInterface $slugger,
    ) {
    }

    // ---- Établissement (logo + bandeau, même dossier "images") ----

    public function etablissementImageDir(int $etablissementId): string
    {
        $dir = $this->etablissementsBaseDir.'/'.$etablissementId.'/images';
        $this->filesystem->mkdir($dir);

        return $dir;
    }

    public function etablissementImageUrl(?int $etablissementId, ?string $filename): ?string
    {
        if (!$filename || !$etablissementId) {
            return null;
        }

        return '/uploads/etablissements/'.$etablissementId.'/images/'.$filename;
    }

    public function etablissementLogoDir(Etablissement $etablissement): string
    {
        return $this->etablissementImageDir($etablissement->getId());
    }

    public function etablissementLogoUrl(?Etablissement $etablissement): ?string
    {
        if (!$etablissement) {
            return null;
        }

        return $this->etablissementImageUrl($etablissement->getId(), $etablissement->getLogoName());
    }

    public function etablissementHeaderDir(Etablissement $etablissement): string
    {
        return $this->etablissementImageDir($etablissement->getId());
    }

    public function etablissementHeaderUrl(?Etablissement $etablissement): ?string
    {
        if (!$etablissement) {
            return null;
        }

        return $this->etablissementImageUrl($etablissement->getId(), $etablissement->getHeaderName());
    }

    /**
     * Préfixe de dossier (sans nom de fichier) — pour les composants qui construisent
     * eux-mêmes l'URL finale (ex: bloc_insert_image.html.twig, url_file ~ entity_name).
     */
    public function etablissementImageUrlPrefixFor(Etablissement $etablissement): string
    {
        return '/uploads/etablissements/'.$etablissement->getId().'/images/';
    }

    /**
     * Nom de fichier déterministe pour un média d'établissement : "{slug du nom}_{suffixe}.{extension}"
     * (ex: pays-des-luys_bandeau.jpg). Chaque établissement a son propre dossier, donc aucun
     * risque de collision avec un autre établissement.
     */
    private function etablissementMediaFilename(Etablissement $etablissement, string $suffix, string $extension): string
    {
        $slug = strtolower($this->slugger->slug((string) $etablissement->getName()));

        return $slug.'_'.$suffix.'.'.$extension;
    }

    public function etablissementHeaderFilename(Etablissement $etablissement, string $extension): string
    {
        return $this->etablissementMediaFilename($etablissement, 'bandeau', $extension);
    }

    public function etablissementLogoFilename(Etablissement $etablissement, string $extension): string
    {
        return $this->etablissementMediaFilename($etablissement, 'avatar', $extension);
    }

    // ---- Article : image de présentation ----

    public function articleImageDir(?int $etablissementId, int $authorId): string
    {
        $dir = $etablissementId
            ? $this->etablissementsBaseDir.'/'.$etablissementId.'/articles'
            : $this->adminsBaseDir.'/'.$authorId.'/articles';
        $this->filesystem->mkdir($dir);

        return $dir;
    }

    public function articleImageUrl(?int $etablissementId, int $authorId, ?string $filename): ?string
    {
        if (!$filename) {
            return null;
        }

        return $etablissementId
            ? '/uploads/etablissements/'.$etablissementId.'/articles/'.$filename
            : '/uploads/admins/'.$authorId.'/articles/'.$filename;
    }

    public function articleImageDirFor(Article $article): string
    {
        return $this->articleImageDir(
            $article->getEtablissement()?->getId(),
            $article->getAuthor()?->getId() ?? 0
        );
    }

    public function articleImageUrlFor(Article $article): ?string
    {
        return $this->articleImageUrl(
            $article->getEtablissement()?->getId(),
            $article->getAuthor()?->getId() ?? 0,
            $article->getImageName()
        );
    }

    /**
     * Préfixe de dossier (sans nom de fichier) — pour les composants qui construisent
     * eux-mêmes l'URL finale (ex: bloc_insert_image.html.twig, url_file ~ entity_name).
     */
    public function articleImageUrlPrefixFor(Article $article): string
    {
        $etablissementId = $article->getEtablissement()?->getId();

        return $etablissementId
            ? '/uploads/etablissements/'.$etablissementId.'/articles/'
            : '/uploads/admins/'.($article->getAuthor()?->getId() ?? 0).'/articles/';
    }

    /**
     * Nom de fichier déterministe pour un média d'article : "{slug de l'article}-{id}_{suffixe}.{extension}".
     * Contrairement à un établissement, le dossier d'un article est partagé avec tous les autres
     * articles du même propriétaire (établissement ou admin) — l'ID est donc nécessaire pour
     * éviter qu'un article dont le titre produit le même slug qu'un autre écrase son fichier.
     */
    private function articleMediaFilename(Article $article, string $suffix, string $extension): string
    {
        return $article->getSlug().'-'.$article->getId().'_'.$suffix.'.'.$extension;
    }

    public function articleImageFilename(Article $article, string $extension): string
    {
        return $this->articleMediaFilename($article, 'article', $extension);
    }

    /**
     * Suffixe de nommage ('audio'|'video'|'doc') correspondant au sous-dossier du support
     * (audios/videos/docs), déduit du support déjà renseigné sur l'article au moment de l'appel.
     */
    public function articleDocFilename(Article $article, string $extension): string
    {
        $suffix = match ($this->resolveDocSubfolder($article->getSupport()?->getId(), null)) {
            'audios' => 'audio',
            'videos' => 'video',
            default => 'doc',
        };

        return $this->articleMediaFilename($article, $suffix, $extension);
    }

    // ---- Article : pièce jointe (doc), rangée selon le support (audio/vidéo/document) ----

    /**
     * Détermine le sous-dossier ('audios'|'videos'|'docs') selon l'ID du support.
     * Si le support est absent ou vaut "Aucun" (id 0), on retombe sur l'extension
     * du fichier ; si même l'extension est inexploitable, on range dans 'docs'.
     */
    public function resolveDocSubfolder(?int $supportId, ?string $filenameHint = null): string
    {
        $bySupportId = match ($supportId) {
            self::SUPPORT_AUDIO => 'audios',
            self::SUPPORT_VIDEO => 'videos',
            self::SUPPORT_DOCUMENT => 'docs',
            default => null,
        };

        if (null !== $bySupportId) {
            return $bySupportId;
        }

        $extension = $filenameHint ? strtolower(pathinfo($filenameHint, PATHINFO_EXTENSION)) : '';

        return match (true) {
            in_array($extension, self::AUDIO_EXTENSIONS, true) => 'audios',
            in_array($extension, self::VIDEO_EXTENSIONS, true) => 'videos',
            in_array($extension, self::DOCUMENT_EXTENSIONS, true) => 'docs',
            default => 'docs',
        };
    }

    public function articleDocDir(?int $etablissementId, int $authorId, ?int $supportId, ?string $filenameHint = null): string
    {
        $subfolder = $this->resolveDocSubfolder($supportId, $filenameHint);
        $dir = $etablissementId
            ? $this->etablissementsBaseDir.'/'.$etablissementId.'/'.$subfolder
            : $this->adminsBaseDir.'/'.$authorId.'/'.$subfolder;
        $this->filesystem->mkdir($dir);

        return $dir;
    }

    public function articleDocUrl(?int $etablissementId, int $authorId, ?int $supportId, ?string $filename): ?string
    {
        if (!$filename) {
            return null;
        }

        $subfolder = $this->resolveDocSubfolder($supportId, $filename);

        return $etablissementId
            ? '/uploads/etablissements/'.$etablissementId.'/'.$subfolder.'/'.$filename
            : '/uploads/admins/'.$authorId.'/'.$subfolder.'/'.$filename;
    }

    public function articleDocDirFor(Article $article): string
    {
        return $this->articleDocDir(
            $article->getEtablissement()?->getId(),
            $article->getAuthor()?->getId() ?? 0,
            $article->getSupport()?->getId(),
            $article->getDoc()
        );
    }

    public function articleDocUrlFor(Article $article): ?string
    {
        return $this->articleDocUrl(
            $article->getEtablissement()?->getId(),
            $article->getAuthor()?->getId() ?? 0,
            $article->getSupport()?->getId(),
            $article->getDoc()
        );
    }

    /**
     * Préfixe de dossier (sans nom de fichier) — pour les composants qui construisent
     * eux-mêmes l'URL finale (ex: bloc_insert_image.html.twig, url_file ~ entity_name).
     */
    public function articleDocUrlPrefixFor(Article $article): string
    {
        $etablissementId = $article->getEtablissement()?->getId();
        $authorId = $article->getAuthor()?->getId() ?? 0;
        $subfolder = $this->resolveDocSubfolder($article->getSupport()?->getId(), $article->getDoc());

        return $etablissementId
            ? '/uploads/etablissements/'.$etablissementId.'/'.$subfolder.'/'
            : '/uploads/admins/'.$authorId.'/'.$subfolder.'/';
    }

    // ---- Admin : préparé pour un futur usage (aucun aujourd'hui, pas d'image "profil admin") ----

    public function adminImageDir(int $userId): string
    {
        $dir = $this->adminsBaseDir.'/'.$userId.'/images';
        $this->filesystem->mkdir($dir);

        return $dir;
    }

    /**
     * Ajoute imageUrl/docUrl/logoUrl à une ligne d'article projetée en tableau (résultats DQL
     * partiels), en s'adaptant aux différents noms de clés utilisés selon la méthode du
     * repository d'origine (idetablissement/idEtablissement, logoName/logoNameEtablissement...).
     */
    public function withArticleMediaUrls(?array $row): ?array
    {
        if (null === $row) {
            return null;
        }

        $etablissementId = $row['idEtablissement'] ?? $row['idetablissement'] ?? null;
        $authorId = $row['idauthor'] ?? $row['idAuthor'] ?? 0;

        if (array_key_exists('imageName', $row)) {
            $row['imageUrl'] = $this->articleImageUrl($etablissementId, $authorId, $row['imageName']);
        }

        if (array_key_exists('doc', $row)) {
            $supportId = $row['idsupport'] ?? $row['idSupport'] ?? null;
            $row['docUrl'] = $this->articleDocUrl($etablissementId, $authorId, $supportId, $row['doc']);
        }

        foreach (['logoName', 'logoNameEtablissement', 'logoEtablissement'] as $logoKey) {
            if (array_key_exists($logoKey, $row)) {
                $row['logoUrl'] = $this->etablissementImageUrl($etablissementId, $row[$logoKey]);
                break;
            }
        }

        return $row;
    }
}

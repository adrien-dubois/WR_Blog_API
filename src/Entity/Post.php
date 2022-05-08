<?php

namespace App\Entity;

use App\Repository\PostRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Vich\UploaderBundle\Mapping\Annotation as Vich;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=PostRepository::class)
 * @Vich\Uploadable
 */
class Post
{
    /**
     * @ORM\Id
     * @ORM\GeneratedValue
     * @ORM\Column(type="integer")
     * @Groups({"post"})
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=255)
     * @Groups({"post"})
     */
    private $title;

    /**
     * @ORM\Column(type="text")
     * @Groups({"post"})
     */
    private $text;

    /**
     * @Assert\File(
     *      maxSize = "1M",
     *      maxSizeMessage = "Taille maximale autorisée : {{ limit }} {{ suffix }}.",
     *      mimeTypes = {"image/png", "image/jpg", "image/jpeg"},
     *      mimeTypesMessage = "Formats autorisés : png, jpg, jpeg."
     * )
     * @Vich\UploadableField(mapping="product_image", fileNameProperty="picture")
     *
     * @var File|null
     */
    private $imageFile;

    /**
     * @ORM\Column(type="text", nullable=true)
     * @Groups({"post"})
     */
    private $picture;

    /**
     * @ORM\Column(type="string", length=255, nullable=true)
     * @Groups({"post"})
     */
    private $links;

    /**
     * @ORM\Column(type="datetime_immutable")
     * @Groups({"post"})
     */
    private $createdAt;

    /**
     * @ORM\Column(type="datetime_immutable", nullable=true)
     * @Groups({"post"})
     */
    private $updatedAt;

    /**
     * @ORM\ManyToMany(targetEntity=Tag::class, mappedBy="post")
     * @Groups({"post"})
     */
    private $tags;

    /**
     * @ORM\ManyToMany(targetEntity=Categories::class, inversedBy="posts")
     * @Groups({"post"})
     */
    private $categories;

    /**
     * @ORM\OneToMany(targetEntity=Comment::class, mappedBy="post")
     * @Groups({"post"})
     */
    private $comment;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="posts")
     * @Groups({"post"})
     */
    private $user;

    public function __construct()
    {
        $this->tags = new ArrayCollection();
        $this->categories = new ArrayCollection();
        $this->comment = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setText(string $text): self
    {
        $this->text = $text;

        return $this;
    }

    public function getPicture(): ?string
    {
        return $this->picture;
    }

    public function setPicture(?string $picture): self
    {
        $this->picture = $picture;

        return $this;
    }

    public function getLinks(): ?string
    {
        return $this->links;
    }

    public function setLinks(?string $links): self
    {
        $this->links = $links;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    /**
     * @return Collection<int, Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function addTag(Tag $tag): self
    {
        if (!$this->tags->contains($tag)) {
            $this->tags[] = $tag;
            $tag->addPost($this);
        }

        return $this;
    }

    public function removeTag(Tag $tag): self
    {
        if ($this->tags->removeElement($tag)) {
            $tag->removePost($this);
        }

        return $this;
    }

    /**
     * @return Collection<int, Categories>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    public function addCategory(Categories $category): self
    {
        if (!$this->categories->contains($category)) {
            $this->categories[] = $category;
        }

        return $this;
    }

    public function removeCategory(Categories $category): self
    {
        $this->categories->removeElement($category);

        return $this;
    }

    /**
     * @return Collection<int, Comment>
     */
    public function getComment(): Collection
    {
        return $this->comment;
    }

    public function addComment(Comment $comment): self
    {
        if (!$this->comment->contains($comment)) {
            $this->comment[] = $comment;
            $comment->setPost($this);
        }

        return $this;
    }

    public function removeComment(Comment $comment): self
    {
        if ($this->comment->removeElement($comment)) {
            // set the owning side to null (unless already changed)
            if ($comment->getPost() === $this) {
                $comment->setPost(null);
            }
        }

        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

        return $this;
    }

    /**
     * Get the value of imageFile
     *
     * @return  File|null
     */
    public function getImageFile()
    {
        return $this->imageFile;
    }

    /**
     * Set the value of imageFile
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $imageFile 
     *
     * 
     */
    public function setImageFile($imageFile)
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
             // Il est nécessaire qu'au moins un champ change si vous utilisez doctrine 
            // sinon les écouteurs d'événement ne seront pas appelés et le fichier est perdu 
            $this->updatedAt = new \DateTimeImmutable();
        }
    }
}

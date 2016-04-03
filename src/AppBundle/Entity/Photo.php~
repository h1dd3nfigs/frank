<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;

/**
 * Photo
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Photo
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="ali_img_url", type="string", length=255)
     */
    private $aliImgUrl;

    /**
     * @var string
     *
     * @ORM\Column(name="aws_img_url", type="string", length=255)
     */
    private $awsImgUrl;

    /**
     * @var integer
     *
     * @ORM\Column(name="ali_helpful_count", type="smallint")
     */
    private $aliHelpfulCount;

    /**
     * @var integer
     *
     * @ORM\Column(name="ali_upload_date", type="integer")
     */
    private $ali_upload_date;


    /**
     * @var integer
     *
     * @ORM\Column(name="rating", type="smallint")
     */
    private $rating;

    /**
     * @ORM\ManyToOne(targetEntity="Product", inversedBy="photos")
     * @ORM\JoinColumn(name="product_id", referencedColumnName="id")
     */
    protected $product;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set aliImgUrl
     *
     * @param string $aliImgUrl
     *
     * @return Photo
     */
    public function setAliImgUrl($aliImgUrl)
    {
        $this->aliImgUrl = $aliImgUrl;

        return $this;
    }

    /**
     * Get aliImgUrl
     *
     * @return string
     */
    public function getAliImgUrl()
    {
        return $this->aliImgUrl;
    }

    /**
     * Set awsImgUrl
     *
     * @param string $awsImgUrl
     *
     * @return Photo
     */
    public function setAwsImgUrl($awsImgUrl)
    {
        $this->awsImgUrl = $awsImgUrl;

        return $this;
    }

    /**
     * Get awsImgUrl
     *
     * @return string
     */
    public function getAwsImgUrl()
    {
        return $this->awsImgUrl;
    }

    /**
     * Set aliHelpfulCount
     *
     * @param integer $aliHelpfulCount
     *
     * @return Photo
     */
    public function setAliHelpfulCount($aliHelpfulCount)
    {
        $this->aliHelpfulCount = $aliHelpfulCount;

        return $this;
    }

    /**
     * Get aliHelpfulCount
     *
     * @return integer
     */
    public function getAliHelpfulCount()
    {
        return $this->aliHelpfulCount;
    }

    /**
     * Set rating
     *
     * @param integer $rating
     *
     * @return Photo
     */
    public function setRating($rating)
    {
        $this->rating = $rating;

        return $this;
    }

    /**
     * Get rating
     *
     * @return integer
     */
    public function getRating()
    {
        return $this->rating;
    }

    /**
     * Set product
     *
     * @param \AppBundle\Entity\Product $product
     *
     * @return Photo
     */
    public function setProduct(\AppBundle\Entity\Product $product = null)
    {
        $this->product = $product;

        return $this;
    }

    /**
     * Get product
     *
     * @return \AppBundle\Entity\Product
     */
    public function getProduct()
    {
        return $this->product;
    }

    /**
     * Set aliUploadDate
     *
     * @param integer $aliUploadDate
     *
     * @return Photo
     */
    public function setAliUploadDate($aliUploadDate)
    {
        $this->ali_upload_date = $aliUploadDate;

        return $this;
    }

    /**
     * Get aliUploadDate
     *
     * @return integer
     */
    public function getAliUploadDate()
    {
        return $this->ali_upload_date;
    }
}

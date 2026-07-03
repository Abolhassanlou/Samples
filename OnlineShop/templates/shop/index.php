<?php require_once 'layout/header.php'; ?>
<?php require_once 'layout/navbar.php'; ?>

<section class="hero">

    <img
        src="../../assets/images/hero.jpg"
        alt="Hero Banner"
    >

</section>

<section class="category-section">
    <div class="section-header">
        <h2>Our Categories</h2>
        <a href="products.php">View all categories ›</a>
    </div>

    <div class="category-grid">
        <a href="products.php?category_id=1" class="category-card">
            <img src="../../assets/images/category-fruit.jpg" alt="Fruits">
            <h3>Fruits</h3>
            <span>Discover now ›</span>
        </a>

        <a href="products.php?category_id=2" class="category-card">
            <img src="../../assets/images/category-vegetables.jpg" alt="Vegetables">
            <h3>Vegetables</h3>
            <span>Discover now ›</span>
        </a>

        <a href="products.php?category_id=3" class="category-card">
            <img src="../../assets/images/category-drink.jpg" alt="Drinks">
            <h3>Drinks</h3>
            <span>Discover now ›</span>
        </a>
    </div>
</section>

<?php require_once 'layout/footer.php'; ?>
<style>
 .hero{
    width:100%;
    margin:10;
}

.hero img{
    width:100%;
    height:400px;
    object-fit:cover;
    display:block;
}
.category-grid {
    display: grid;
    grid-template-columns: repeat(3 , 1fr);
    gap: 20px;
}
.category-card {
    display:block;
    text-decoration: none;
    overflow: hidden;
    border-radius: 10px;
    transition: all .3s ease;
    
}
.category-card img{
    width: 100%;
    height: 250px;
    object-fit: cover;
    display:block;
    border-radius: 10px 10px 0 0;
}
.category-card h3{
    margin:15px 0 5px;
}
.category-card span{
    color: #d32f2f;
}
.category-card:hover{
    transform: translateY(-6px);
    box-shadow: 0 10px 25px rgba(0,0,0,.15);
}
</style>
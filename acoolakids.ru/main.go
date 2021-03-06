/*
Catalog parser for https://acoolakids.ru
*/
package main

import ( // {{{
	"encoding/json"
	//	"sort"
	"sync"
	//	"regexp"
	"fmt"

	"github.com/PuerkitoBio/goquery"
	//	"github.com/fiam/gounidecode/unidecode"
	//	"io"
	"io/ioutil"
	//	"os"
	//	"path/filepath"
	"strconv"
	"strings"
	"time"
) // }}}

/*
Product structure
*/
type Product struct { // {{{
	Sku      string            `json:"sku"`
	Category string            `json:"category"`
	Name     string            `json:"name"`
	Desc     map[string]string `json:"desc"`
	Pict     string            `json:"pict"`
	Link     string            `json:"link"`
	//	Desc []string `json:"desc"`
} // }}}

/*
Products array
*/
type Products []Product

var ( // {{{
	first, last, curr int
	doc               *goquery.Document
	currDoc           *goquery.Document
	currProduct       *goquery.Document
	products          Products
	categories        = make(map[string]string)
	err               error
	counter           int
	wg                sync.WaitGroup
) // }}}

const ( // {{{
	baseURL    = "http://acoolakids.ru"
	outputDir  = "images"
	outputJSON = "acoolakids.json"
) // }}}

func checkErr(err error) { // {{{
	if err != nil {
		fmt.Println(err)
	}
} // }}}

func getFilename(url string) string { // {{{
	tokens := strings.Split(url, "/")
	return tokens[len(tokens)-1]
} // }}}

/* func translit(s string) string {// {{{
	return unidecode.Unidecode(strings.Replace(s, " ", "_", -1))
}

func downloadFromURL(url string, outputDir string) error {
	fileName := getFilename(url)
	output, err := os.Create(outputDir + string(filepath.Separator) + fileName)
	if err != nil {
		log.Println("Ошибка при создании", outputDir+string(filepath.Separator)+fileName, "-", err)
		return err
	}
	defer output.Close()

	response, err := http.Get(url)
	if err != nil {
		log.Println("Ошибка при загрузке", url, "-", err)
		return err
	}
	defer response.Body.Close()

	n, err := io.Copy(output, response.Body)
	if err != nil {
		log.Println("Ошибка при загрузке", url, "-", err)
		return err
	}

	//	fmt.Println(n, "байтов загружено.")
	if n == 0 {
		return errors.New("Null size image")
	} else {
		return nil
	}
} */ // }}}

func contains(slice []string, item string) bool { // {{{
	set := make(map[string]struct{}, len(slice))
	for _, s := range slice {
		set[s] = struct{}{}
	}

	_, ok := set[item]
	return ok
} // }}}

func getCategories() map[string]string { // {{{
	excludeList := []string{
		"Вся одежда",
	}
	cats := map[int]string{1: "Девочки", 2: "Мальчики"}
	fmt.Println("* Поиск категорий")
	//	reg, _ := regexp.Compile(`\s\([0-9]+\)`)

	doc, _ := goquery.NewDocument(baseURL + "/sitemap")
	for key, value := range cats {
		doc.Find("#layout > div.sitemap-w > div.sitemap-cnt-w > div:nth-child(" + strconv.Itoa(key) + ") > ul:nth-child(2) > li").Each(func(i int, s *goquery.Selection) {
			catLink, _ := s.Find("a").Attr("href")
			cat := s.Find("a").Text()
			if !contains(excludeList, cat) {
				fmt.Printf("** %s - %s => %s\n", value, cat, catLink)
				categories[value+" - "+cat] = catLink
			}
		})
	}

	fmt.Println("* Поиск категорий закончен")
	return categories
} // }}}

func getPages(url string) int { // {{{
	doc, _ := goquery.NewDocument(url)
	last, _ = strconv.Atoi(doc.Find("div.pager > div.pages > ol > li > a:not(.next)").Last().Text())
	if last = last; last == 0 {
		last = 1
	}
	// fmt.Printf("Найдено страниц - %d\n", last)
	return last
} // }}}

func walkinPages() { // {{{
	fmt.Printf("* Начинаем обработку страниц\n")
	for cat, catLink := range categories {
		//		last := getPages(catLink)
		//		for i := 1; i <= last; i++ {
		//		reqAjaxLink := "https://tvoe.ru/ajax/infinite-scrolling/catalog/category/view/page/" + strconv.Itoa(i) + "/limit/1000/requested-url"
		//		tmpcatLink := strings.Replace(catLink, baseURL, "", 1)
		// fmt.Printf("\t%s => %s\n", cat, catLink)
		currPage := fmt.Sprintf("%s%s%s", baseURL, catLink, "?page=all")
		// log.Printf("** Открываем страницу %s\n", currPage)
		// fmt.Printf("** Обработка страницы %s - %s\n", cat, currPage)
		// fmt.Printf("**\t Обработка страницы %d\n", i)
		doc, _ = goquery.NewDocument(currPage)
		getProducts(doc, cat)
		// fmt.Println()
		// log.Printf("** Закончили\n")
		//		}
	}
	wg.Wait()
	fmt.Printf("\n** Закончили обработку страниц\n")
} // }}}

func getProducts(doc *goquery.Document, cat string) { // {{{
	doc.Find("#content > div.catalog > div.product-list-w > ul.product_list > li:not(.pagination-list-w)").Each(func(i int, s *goquery.Selection) {
		categories[strings.Split(s.Find("a > h3").Text(), " ")[0]] = ""
		tmpLink, _ := s.Find("a:first-child").Attr("href")
		go getProductInfo(tmpLink, cat)
		wg.Add(1)
	})
	wg.Wait()
} // }}}

func getProductInfo(url string, cat string) { // {{{
	defer wg.Done()
	docp, err := goquery.NewDocument(url)
	checkErr(err)

	p := Product{}
	tmpSKU := docp.Find("div#tabs > div#d_product_desq > div.d_product_desq > span").Text()
	if tmpSKU == "" {
		return
	}
	p.Sku = strings.TrimSpace(strings.Replace(tmpSKU, "Артикул", "", 1))
	p.Name = docp.Find("div#tabs > div#d_product_desq > div.d_product_desq > h1").Text()
	p.Pict, _ = docp.Find("div.d_product.d_product-tall > div > div.d_product_photo > div.b-image-slider > div.b-image-slider--container > div.b-image-slider--inner > ul > li:first-child > a:first-child > img").Attr("src")
	//	tmpPict, _ := docp.Find("div.d_product.d_product-tall > div > div.d_product_photo > div.b-image-slider > div.b-image-slider--container > div.b-image-slider--inner > ul > li:first-child > a:first-child > img").Html()
	//	tmpPict, _ := docp.Find("div.d_product.d_product-tall > div > div.d_product_photo > div.b-image-slider > div.b-image-slider--container > div.b-image-slider--inner > ul > li:first-child > a:first-child > img").Attr("src")
	//	fmt.Printf("tmpPict - %s on %s\n", tmpPict, url)
	p.Link = url
	//	p.Category = strings.Split(p.Name, " ")[0]
	p.Category = cat

	desc := make(map[string]string)
	docp.Find("div#tabs > div#d_product_desq > div.d_product_desq > dl").Each(func(i int, s *goquery.Selection) {
		title := s.Find("dt").Text()
		//		fmt.Printf("title - %s, content - %s, URL - %s\n", title, s.Find("dd").Text(), url)
		if title != "" {
			tmpTitle := strings.TrimRight(title, ":")
			//			fmt.Printf("tmpTitle - %s\n", tmpTitle)
			desc[tmpTitle] = strings.TrimSpace(s.Find("dd").Text())
		} else {
			desc["Описание"] = strings.Replace(s.Find("dd").Text(), "\n", "<br>", -1)
		}
	})
	p.Desc = desc

	//	fmt.Printf("%+v\n", p)

	products = append(products, p)
	counter++
	fmt.Print(".")
} // }}}

func main() { // {{{
	ts := time.Now()
	/*
		if _, err := os.Stat(outputDir); os.IsNotExist(err) {
			os.MkdirAll(outputDir, 0777)
		}
	*/
	fmt.Println("* Начинаем сбор страниц")
	categories = getCategories()

	walkinPages()
	//	doc, _ := goquery.NewDocument(baseURL + "?page=all")
	//	getProducts(doc)

	jsonProducts, _ := json.MarshalIndent(products, "", "  ")
	err = ioutil.WriteFile(outputJSON, jsonProducts, 0644)
	if err != nil {
		panic(err)
	}
	fmt.Println("\n* Сбор страниц закончен")

	/* var cats []string
	for cat := range categories {
		cats = append(cats, cat)
	}
	sort.Strings(cats)

	for _, cat := range cats {
		fmt.Printf("%s => %s\n", cat, categories[cat])
	} */

	fmt.Printf("* Обработано продуктов %d\n", counter)
	te := time.Now()
	d := te.Sub(ts)
	fmt.Printf("Общее время выполнения в секундах - %d\n", int(d.Seconds()))
} // }}}

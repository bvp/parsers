/*
Catalog parser for https://tvoe.ru
*/
package main

import (
	"encoding/json"
	"html"
	"net/http"
	"strconv"
	"sync"
	//	"regexp"
	"fmt"
	"github.com/PuerkitoBio/goquery"
	//	"github.com/fiam/gounidecode/unidecode"
	//	"io"
	"io/ioutil"
	//	"os"
	//	"path/filepath"
	"strings"
	"time"
)

/*
Product structure
*/
type Product struct {
	Sku      string   `json:"sku"`
	Category string   `json:"category"`
	Name     string   `json:"name"`
	Desc     []string `json:"desc"`
	Pict     string   `json:"pict"`
	Link     string   `json:"link"`
	// Desc     map[string]string `json:"desc"`
	// Desc []string `json:"desc"`
}

/*
Products array
*/
type Products []Product

type productFromJSON struct {
	Collection      []string `json:"collection"`
	Color           []string `json:"color"`
	Colors          []string `json:"colors"`
	Currency        string   `json:"currency"`
	ID              string   `json:"id"`
	Img             string   `json:"img"`
	Large           bool     `json:"large"`
	Link            string   `json:"link"`
	Name            string   `json:"name"`
	New             bool     `json:"new"`
	OldPrice        string   `json:"oldPrice"`
	OldPriceDisplay string   `json:"oldPriceDisplay"`
	Percent         string   `json:"percent"`
	Popular         string   `json:"popular"`
	Price           string   `json:"price"`
	PriceDisplay    string   `json:"priceDisplay"`
	Sale            bool     `json:"sale"`
	Size            []string `json:"size"`
}

type catalog struct {
	Category string
	Products
}

var (
	first, last, curr int
	doc               *goquery.Document
	currDoc           *goquery.Document
	currProduct       *goquery.Document
	products          Products
	productsFromJSON  []productFromJSON
	catalogs          []catalog
	categories        = make(map[string]string)
	err, errCP        error
	counter           int
	wg                sync.WaitGroup
	excludeList       = []string{
		"SALE",
	}
)

const (
	baseURL    = "http://shop.pelican-style.ru"
	outputDir  = "images"
	outputJSON = "pelican-style.json"
)

func checkErr(err error) {
	if err != nil {
		fmt.Println(err)
	}
}

func getFilename(url string) string {
	tokens := strings.Split(url, "/")
	return tokens[len(tokens)-1]
}

/* func translit(s string) string {
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
} */

func contains(slice []string, item string) bool {
	set := make(map[string]struct{}, len(slice))
	for _, s := range slice {
		set[s] = struct{}{}
	}

	_, ok := set[item]
	return ok
}

func getCategories() map[string]string {
	//	var cat, subCat, subCatLink string
	fmt.Println("* Поиск категорий")
	//	reg, _ := regexp.Compile(`\s\([0-9]+\)`)

	/*
		doc, _ := goquery.NewDocument(baseURL + "/zhenschiny")
		// doc.Find("ul#cat_menu > li a:has(span)").Each(func(i int, s *goquery.Selection) {
		// doc.Find("ul#cat_menu > li:has(a:has(span))").Each(func(i int, s *goquery.Selection) {
		doc.Find("#filter-cats > ol > li").Each(func(i int, s *goquery.Selection) {
			catLink, _ := s.Find("a").Attr("href")
			cat, _ := s.Find("a").Attr("title")
			if !contains(excludeList, cat) {
				log.Printf("** %s => %s", cat, catLink)
				doc.Find("div.tab-content > div#menu-cat-" + strconv.Itoa(i+1) + "-1 > ul.list-menu").Each(func(j int, dis *goquery.Selection) {
					dis.Find("li").Each(func(i int, disSubCat *goquery.Selection) {
						innerCat := strings.TrimSpace(disSubCat.Find("a").Text())
						innerCatLink, _ := disSubCat.Find("a").Attr("href")

						if !contains(excludeList, innerCat) {
							log.Printf("**\t %s => %s", innerCat, innerCatLink)
							categories[cat+" - "+innerCat] = innerCatLink
							//				log.Printf("** Найдена категория - %s => %s\n", innerCat, innerCatLink)

							//				getPages(baseURL + innerCatLink)
						}
					})
				})
			}
		})
	*/

	doc, _ := goquery.NewDocument(baseURL)
	mainCategorySize := doc.Find("body > div.b-wrap-all > div > header > div.b-head-bot > ul.b-cat-nav > li").Size()
	for i := 1; i <= mainCategorySize; i++ {
		catTitle := doc.Find("body > div.b-wrap-all > div > header > div.b-head-bot > ul.b-cat-nav > li:nth-child(" + strconv.Itoa(i) + ") > a:first-child").Text()
		var subSubCatSize int
		if !contains(excludeList, catTitle) {
			q2 := "body > div.b-wrap-all > div > header > div.b-head-bot > ul.b-cat-nav > li:nth-child(" + strconv.Itoa(i) + ") > ul > li"
			subCatSize := doc.Find(q2).Size()
			for j := 1; j <= subCatSize; j++ {
				subCatTitle := doc.Find(q2 + ":nth-child(" + strconv.Itoa(j) + ") > a:first-child").Text()
				q3 := "body > div.b-wrap-all > div > header > div.b-head-bot > ul.b-cat-nav > li:nth-child(" + strconv.Itoa(i) + ") > ul > li:nth-child(" + strconv.Itoa(j) + ") > ul > li"
				subSubCatSize = doc.Find(q3).Size()
				for k := 1; k <= subSubCatSize; k++ {
					subSubCatTitle := doc.Find(q3 + ":nth-child(" + strconv.Itoa(k) + ") > a").Text()
					subSubCatLink, _ := doc.Find(q3 + ":nth-child(" + strconv.Itoa(k) + ") > a").Attr("href")
					//					fmt.Printf("Category Title - %s, subSubCatTitle - %s, subSubCatLink - %s\n", catTitle, subSubCatTitle, subSubCatLink)
					//					fmt.Printf("**\t %s - %s - %s => %s\n", catTitle, subCatTitle, subSubCatTitle, baseURL+subSubCatLink)
					//					fmt.Printf("'%s - %s - %s' => ,\n", catTitle, subCatTitle, subSubCatTitle)
					categories[catTitle+" - "+subCatTitle+" - "+subSubCatTitle] = baseURL + subSubCatLink
				}
			}
		}
	}

	fmt.Println("* Поиск категорий закончен")
	return categories
}

func getPages(url string) int {
	scrMap := make(map[string]string)
	doc, _ := goquery.NewDocument(url)
	doc.Find("script:not([type]):not([src]):not([id])").Each(func(i int, qp *goquery.Selection) {
		scriptContent, _ := qp.Html()
		strs := strings.Split(strings.TrimSpace(html.UnescapeString(scriptContent)), "\n")
		for _, v := range strs {
			v = strings.Replace(v, ";", "", 1)
			vals := strings.Split(strings.TrimSpace(v), " = ")
			key := vals[0]
			val := strings.Replace(vals[1], "'", "", -1)
			scrMap[key] = val
			//			fmt.Printf("%s[%s]\n", key, val)
		}
	})
	last, _ = strconv.Atoi(scrMap["CatalogHelper.maxPage"])
	first, _ = strconv.Atoi(scrMap["CatalogHelper.startPage"])
	if last = last; last == 0 {
		last = 1
	}
	//	fmt.Printf("Найдено страниц - %v\n", last)
	return last
}

func walkinPages() {
	fmt.Printf("* Начинаем обработку страниц\n")
	for cat, catLink := range categories {
		last := getPages(catLink)
		for i := 1; i <= last; i++ {
			// reqAjaxLink := catLink + "?ajax_page=Y&PAGEN_1=" + strconv.Itoa(i)
			// tmpcatLink := strings.Replace(catLink, baseURL, "", 1)
			// fmt.Printf("\t%s => %s\n", cat, catLink)
			currPage := catLink + "?ajax_page=Y&PAGEN_1=" + strconv.Itoa(i)
			//			fmt.Printf(" category - %s ", cat)
			getProducts(cat, currPage)
		}
	}
	wg.Wait()
	fmt.Printf("\n* Закончили обработку страниц\n")
}

func getProducts(cat string, url string) {
	//	fmt.Printf("** Обработка страницы %s - %s\n", cat, url)
	client := &http.Client{}
	req, err := http.NewRequest("GET", url, nil)
	req.Header.Add("Accept-Encoding", "identity")
	req.Close = true

	response, err := client.Do(req)
	if err != nil {
		fmt.Printf("ERROR: in walkingPages after client.Do - %s\n", err)
	}

	defer response.Body.Close()

	doc, errCP := goquery.NewDocumentFromResponse(response)
	if errCP != nil {
		fmt.Println("Can't get page", url)
		fmt.Printf("ERROR: %s\n", errCP)
	}
	doc.Find("li.b-item.list-product").Each(func(i int, qp *goquery.Selection) {
		link, _ := qp.Find("div.b-inner > div.b-desc > div.b-title > a").Attr("href")
		img, _ := qp.Find("div.b-inner > div.b-pic > a.product-image > img").Attr("src")
		go getProductInfo(baseURL+link, cat, baseURL+img)
		wg.Add(1)
	})
}

func getProductInfo(url string, cat string, img string) {
	defer wg.Done()
	doc, err := goquery.NewDocument(url)
	checkErr(err)
	p := Product{}
	tmpName := doc.Find("body > div.b-wrap-all > div.b-container > div.b-content > div.b-card.clearfix > div.bc-prod > h1").Text()
	p.Sku = strings.Split(tmpName, " ")[0]
	p.Name = strings.TrimLeft(tmpName, p.Sku+" ")
	p.Pict = img
	p.Link = url

	/* s.Find("article > p").Each(func(j int, ss *goquery.Selection) {
		if j == 0 {
			name := ss.Find("b:nth-child(1)").Text()
			tmpName += name
			if name != "" {
				desc = append(desc, ss.Text())
			}
		} else {
			name := ss.Find("b:nth-child(1)").Text()
			tmpName += " / " + name
			if name != "" {
				desc = append(desc, ss.Text())
			}
		}
	}) */

	/*
		//Ternary equivalent for
		p.Name = tmpName ? tmpName : cat
		if p.Name = cat; tmpName != "" {
			p.Name = tmpName
		}
		tmpSku := s.Find("article > p > b:nth-child(1)").Text()
		tmpDesc := s.Find("article > p, b:nth-child(3)").Text()
		p.Sku = strings.TrimSpace(tmpSku)
		p.Name = strings.TrimSpace(s.Find("div.product_info > h3 > a").Text())

	*/

	p.Category = cat

	var desc []string
	doc.Find("body > div.b-wrap-all > div.b-container > div.b-content > div.b-card.clearfix > div.bc-prod > div.b-text-desc > p").Each(func(i int, dp *goquery.Selection) {
		descContent := strings.TrimSpace(dp.Text())
		desc = append(desc, descContent)
	})
	//	tmpFullDesc := strings.TrimRight(strings.TrimSpace(doc.Find("div.node-product.product > div.row > div.prod-descr-view > div.prod-text").Text()), ":")
	//	if (tmpFullDesc != "") || (tmpFullDesc != "_") {
	//		desc["Описание"] = tmpFullDesc
	//	}
	p.Desc = desc

	// time.Sleep(1 * time.Second)
	products = append(products, p)
	counter++
	//	ct := catalog{cat, products}
	//	catalogs = append(catalogs, ct)
	fmt.Print(".")
}

func main() {
	ts := time.Now()
	/*
		if _, err := os.Stat(outputDir); os.IsNotExist(err) {
			os.MkdirAll(outputDir, 0777)
		}
	*/
	fmt.Println("* Начинаем сбор страниц")
	categories = getCategories()

	//	getPages("http://shop.pelican-style.ru/catalog/khranitelnitsa-lesa/")
	walkinPages()

	jsonProducts, _ := json.MarshalIndent(products, "", "  ")
	err = ioutil.WriteFile(outputJSON, jsonProducts, 0644)
	if err != nil {
		panic(err)
	}
	fmt.Println("\n* Сбор страниц закончен")
	fmt.Printf("* Обработано продуктов %d\n", counter)
	te := time.Now()
	d := te.Sub(ts)
	fmt.Printf("Общее время выполнения в секундах - %d\n", int(d.Seconds()))
}

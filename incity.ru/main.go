/*
Catalog parser for https://www.incity.ru
*/
package main

import (
	"encoding/json"
	"html"
	"net/http"
	"sync"
	//	"regexp"
	"fmt"
	"github.com/PuerkitoBio/goquery"
	//	"github.com/fiam/gounidecode/unidecode"
	//	"io"
	"io/ioutil"
	//	"os"
	//	"path/filepath"
	//	"strconv"
	"strings"
	"time"
)

/*
Product structure
*/
type Product struct {
	Sku      string            `json:"sku"`
	Category string            `json:"category"`
	Name     string            `json:"name"`
	Desc     map[string]string `json:"desc"`
	Pict     string            `json:"pict"`
	Link     string            `json:"link"`
	//	Desc []string `json:"desc"`
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
		"sale",
	}
)

const (
	baseURL    = "http://incity.ru"
	outputDir  = "images"
	outputJSON = "incity.json"
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
	doc.Find("body > div.site_container.js-fix-header > header > div > nav > span").Each(func(i int, s *goquery.Selection) {
		cat := strings.TrimSpace(s.Find("span > a").Text())
		if !contains(excludeList, cat) {
			// fmt.Printf("** %s\n", cat)
			s.Find("div.dropdown > div.dropdown__inner > div.dropdown__menu > div.dropdown__list > div").Each(func(j int, ss *goquery.Selection) {
				subCatLink, _ := ss.Find("a").Attr("href")
				subCat := strings.TrimSpace(ss.Find("a > span").Text())
				if !contains(excludeList, subCat) {
					fmt.Printf("**\t %s - %s => %s\n", cat, subCat, baseURL+subCatLink)
					categories[cat+" - "+subCat] = baseURL + subCatLink
				}
			})
		}
	})

	fmt.Println("* Поиск категорий закончен")
	return categories
}

/*
func getPages(url string) int {
	doc, _ := goquery.NewDocument(url)
	last, _ = strconv.Atoi(doc.Find("#js-pagenav > a:has([data-num])").Last().Text())
	if last = last; last == 0 {
		last = 1
	}
	fmt.Printf("Найдено страниц - %s\n", lastP)
	return lastP
}
*/

func walkinPages() {
	fmt.Printf("* Начинаем обработку страниц\n")
	for cat, catLink := range categories {
		//			last := getPages(catLink)
		//			for i := 1; i <= last; i++ {
		// reqAjaxLink := catLink + "?show=1000"
		// tmpcatLink := strings.Replace(catLink, baseURL, "", 1)
		// fmt.Printf("\t%s => %s\n", cat, catLink)
		currPage := catLink + "?show=1000"
		getProducts(cat, currPage)
	}
	wg.Wait()
	fmt.Printf("\n** Закончили обработку страниц\n")
}

func getProducts(cat string, url string) {
	// log.Printf("** Открываем страницу %s\n", currPage)
	//		fmt.Printf("** Обработка страницы %s - %s\n", cat, currPage)
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
	htmlDoc, _ := doc.Find("script#pageSizeTemplate ~ script").Html()
	result := strings.Replace(htmlDoc, "var catalogProductsList =\n", "", 1)
	result = strings.TrimSpace(html.UnescapeString(result))
	result = strings.Replace(result, "'", "\"", -1)
	endStr := "}]"
	idx := strings.Index(result, endStr)
	result = result[:idx+len(endStr)]
	//		fmt.Printf("%s\n", result)

	if err := json.Unmarshal([]byte(result), &productsFromJSON); err != nil {
		panic(err)
	}

	for _, prod := range productsFromJSON {
		//		fmt.Printf("%d - %s\n", i, prod)
		//			fmt.Printf("%d - Name: %s; Link: %s; Img: %s\n", i+1, prod.Name, prod.Link, prod.Img)
		go getProductInfo(baseURL+prod.Link, cat, baseURL+prod.Img)
		wg.Add(1)
	}
	// fmt.Println()
	// log.Printf("** Закончили\n")
	//			}
}

func getProductInfo(url string, cat string, img string) {
	defer wg.Done()
	doc, err := goquery.NewDocument(url)
	checkErr(err)
	p := Product{}
	fmt.Print(".")
	p.Name = doc.Find("div.product__information > div.product__name > h1").Text()
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

	// p.Category = strings.Split(cat, " - ")[1]
	p.Category = cat

	desc := make(map[string]string)
	doc.Find("div.product__information > div.product__info > div:nth-child(3) > div > div.description__desc > div.description__content > table > tbody > tr").Each(func(i int, dp *goquery.Selection) {
		descLabel := strings.TrimRight(strings.TrimSpace(dp.Find("td:nth-child(1)").Text()), ":")
		descContent := strings.TrimSpace(dp.Find("td:nth-child(2)").Text())
		if descLabel == "Артикул" {
			p.Sku = strings.TrimSpace(dp.Find("td.item_art").Text())
		} else if !(descLabel == "") {
			desc[descLabel] = descContent
		}
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

	//	getPages("https://tvoe.ru/zhenschiny/odezhda")
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

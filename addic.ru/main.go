/*
Catalog parser for http://www.addic.ru
*/
package main

import (
	"encoding/json"
	"regexp"
	//	"errors"
	"fmt"
	"github.com/PuerkitoBio/goquery"
	//	"github.com/fiam/gounidecode/unidecode"
	//	"io"
	"io/ioutil"
	"log"
	//	"net/http"
	//	"os"
	//	"path/filepath"
	"strconv"
	"strings"
	"time"
)

/*
Product structure
*/
type Product struct {
	Sku      string `json:"sku"`
	Category string `json:"category"`
	Name     string `json:"name"`
	//	Desc     map[string]string `json:"desc"`
	Desc []string `json:"desc"`
	Pict string   `json:"pict"`
	Link string   `json:"link"`
}

/*
Products array
*/
type Products []Product

type Catalog struct {
	Category string
	Products
}

var (
	first, last, curr int
	doc               *goquery.Document
	currDoc           *goquery.Document
	currProduct       *goquery.Document
	products          Products
	catalog           []Catalog
	categories        = make(map[string]string)
	err               error
	counter           int
)

const (
	baseURL    = "http://www.addic.ru"
	outputDir  = "images"
	outputJSON = "addic.json"
)

func checkErr(err error) {
	if err != nil {
		log.Fatal(err)
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

func getCategories() map[string]string {
	log.Println("* Поиск категорий")
	reg, _ := regexp.Compile(`\s\([0-9]+\)`)

	doc, _ := goquery.NewDocument(baseURL)
	// doc.Find("ul#cat_menu > li a:has(span)").Each(func(i int, s *goquery.Selection) {
	doc.Find("ul#cat_menu > li:has(a:has(span))").Each(func(i int, s *goquery.Selection) {
		catLink, _ := s.Find("a:has(span)").Attr("href")
		docInner, _ := goquery.NewDocument(baseURL + catLink)
		cat := docInner.Find("#content > div:nth-child(2) > h1").Text()
		//		log.Println("**", cat, "=>")
		docInner.Find(`div#left > ul.menuleftm > li`).Each(func(i int, dis *goquery.Selection) {
			innerCat := reg.ReplaceAllString(dis.Find(`a:not(:containsOwn("Мерчендайзинг"))`).Text(), "")
			if innerCat != "" {
				innerCatLink, _ := dis.Find("a").Attr("href")
				categories[cat+" - "+innerCat] = innerCatLink
				//				log.Printf("** Найдена категория - %s => %s\n", innerCat, innerCatLink)

				//				getPages(baseURL + innerCatLink)
			}
		})
	})
	log.Println("* Поиск категорий закончен")
	return categories
}

func getPages(url string) int {
	doc, _ := goquery.NewDocument(url)
	last, _ = strconv.Atoi(doc.Find("div#pagination > ul.pages > li:last-child > a").Text())
	if last = last; last == 0 {
		last = 1
	}
	//	log.Printf("\tНайдено страниц - %d\n", last)
	return last
}

func walkinPages() {
	log.Printf("* Начинаем обработку страниц")
	for cat, catLink := range categories {
		last := getPages(baseURL + catLink)
		for i := 1; i <= last; i++ {
			tmpcatLink := strings.Replace(catLink, ".html", "-page"+strconv.Itoa(i)+".html", 1)
			// log.Printf("%s => %s\n", cat, catLink)
			currPage := fmt.Sprintf("%s%s", baseURL, tmpcatLink)
			//			log.Printf("** Открываем страницу %s\n", currPage)
			//			log.Printf("** Обработка страницы %s ", cat)
			doc, _ = goquery.NewDocument(currPage)
			getProducts(doc, cat)
			//			fmt.Println()
			//			log.Printf("** Закончили\n")
		}
	}
}

func getProducts(doc *goquery.Document, cat string) {
	doc.Find("div#catalog > div.item").Each(func(i int, s *goquery.Selection) {
		p := Product{}
		// log.Println("*** Парсим продукт", i+1)
		fmt.Print(".")
		p.Sku = strings.TrimSpace(s.Find("div.fitem > div.item_info > a.article").Text())
		tmpName, _ := s.Find("div.fitem > a.img > img").Attr("alt")

		var desc []string
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

		// Ternary equivalent for
		// p.Name = tmpName ? tmpName : cat
		if p.Name = cat; tmpName != "" {
			p.Name = tmpName
		}
		//		tmpSku := s.Find("article > p > b:nth-child(1)").Text()
		//		tmpDesc := s.Find("article > p, b:nth-child(3)").Text()
		//		p.Sku = strings.TrimSpace(tmpSku)

		// p.Name = strings.TrimSpace(s.Find("div.product_info > h3 > a").Text())

		p.Category = cat

		tmpLink, _ := s.Find("div.fitem > div.item_info > a.article").Attr("href")
		p.Link = baseURL + tmpLink

		docProd, _ := goquery.NewDocument(p.Link)
		docProd.Find("#iteminfo > div > div.itemtop > p").Each(func(i int, dp *goquery.Selection) {
			tmpDesc := strings.TrimSpace(dp.Text())
			desc = append(desc, tmpDesc)
		})
		type_mat, _ := docProd.Find("#type_mat").Html()
		desc = append(desc, type_mat)
		p.Desc = desc

		tmpPict, _ := s.Find("div.fitem > a.img > img").Attr("src")
		p.Pict = tmpPict
		//		time.Sleep(1 * time.Second)
		products = append(products, p)
		counter++
	})
	ct := Catalog{cat, products}
	catalog = append(catalog, ct)
}

func main() {
	ts := time.Now()
	/*
		if _, err := os.Stat(outputDir); os.IsNotExist(err) {
			os.MkdirAll(outputDir, 0777)
		}
	*/
	log.Println("* Начинаем сбор страниц")
	//	doc, err := goquery.NewDocument(baseURL)
	//	checkErr(err)
	categories = getCategories()

	//	getPages(doc)
	walkinPages()

	jsonProducts, _ := json.MarshalIndent(products, "", "  ")
	//	jsonProducts, _ := json.MarshalIndent(catalog, "", "  ")
	err = ioutil.WriteFile(outputJSON, jsonProducts, 0644)
	if err != nil {
		panic(err)
	}
	log.Println("\n* Сбор страниц закончен")
	log.Printf("* Обработано продуктов %d\n", counter)
	te := time.Now()
	d := te.Sub(ts)
	log.Printf("Общее время выполнения в секундах - %f\n", d.Seconds())
}

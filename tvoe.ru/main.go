/*
Catalog parser for https://tvoe.ru
*/
package main

import (
	"encoding/json"
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
	wg                sync.WaitGroup
)

const (
	baseURL    = "https://tvoe.ru"
	outputDir  = "images"
	outputJSON = "tvoe.json"
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
	excludeList := []string{
		"Скидки",
		"Новинки",
		"Магазины",
		"TBOE + Adventure Time",
		"TBOE + Disney",
		"ТВОЕ + Мумий Тролль",
		"TBOE + Superman",
	}
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
	doc.Find("#main-nav > ul > li.dropdown").Each(func(i int, s *goquery.Selection) {
		cat := strings.TrimSpace(s.Find("a.dropdown-toggle.menu-main-category").Text())
		if !contains(excludeList, cat) {
			fmt.Printf("** %s\n", cat)
			s.Find("div.dropdown-menu > div > div > div.menu-categories > div.menu-cat-parent > ul > li").Each(func(j int, ss *goquery.Selection) {
				subCatId, _ := ss.Find("a").Attr("href")
				subCat := strings.TrimSpace(ss.Find("a").Text())
				subq := "div.dropdown-menu > div > div > div.menu-categories > div.menu-cat-child > div.tab-content > div" + subCatId // + ".tab-pane"
				s.Find(subq).Each(func(j int, ss *goquery.Selection) {
					ss.Find("ul.list-menu > li").Each(func(k int, sss *goquery.Selection) {
						ssCat := sss.Find("a").Text()
						ssCatLink, _ := sss.Find("a").Attr("href")
						if !contains(excludeList, ssCat) {
							// fmt.Printf("**\t %s - %s => %s\n", subCat, ssCat, ssCatLink)
							// fmt.Printf("'%s - %s - %s' => ,\n",cat, subCat, ssCat)
							fmt.Printf("'%s' => ,\n", ssCat)
							categories[cat+" - "+subCat+" - "+ssCat] = ssCatLink
						}
					})
				})
			})
		}
	})

	fmt.Println("* Поиск категорий закончен")
	return categories
}

func getPages(url string) int {
	doc, _ := goquery.NewDocument(url)
	last, _ = strconv.Atoi(doc.Find("div.pager > div.pages > ol > li > a:not(.next)").Last().Text())
	if last = last; last == 0 {
		last = 1
	}
	// fmt.Printf("Найдено страниц - %d\n", last)
	return last
}

func walkinPages() {
	fmt.Printf("* Начинаем обработку страниц\n")
	for cat, catLink := range categories {
		last := getPages(catLink)
		for i := 1; i <= last; i++ {
			reqAjaxLink := "https://tvoe.ru/ajax/infinite-scrolling/catalog/category/view/page/" + strconv.Itoa(i) + "/limit/1000/requested-url"
			tmpcatLink := strings.Replace(catLink, baseURL, "", 1)
			// fmt.Printf("\t%s => %s\n", cat, catLink)
			currPage := fmt.Sprintf("%s%s", reqAjaxLink, tmpcatLink)
			// log.Printf("** Открываем страницу %s\n", currPage)
			// fmt.Printf("** Обработка страницы %s - %s\n", cat, currPage)
			// fmt.Printf("**\t Обработка страницы %d\n", i)
			doc, _ = goquery.NewDocument(currPage)
			wg.Add(1)
			go getProducts(doc, cat)
			// fmt.Println()
			// log.Printf("** Закончили\n")
		}
	}
	wg.Wait()
	fmt.Printf("\n** Закончили обработку страниц\n")
}

func getProducts(doc *goquery.Document, cat string) {
	defer wg.Done()
	doc.Find("div.catalog > ul > li").Each(func(i int, s *goquery.Selection) {
		p := Product{}
		fmt.Print(".")
		p.Name = s.Find("div.product-block > div.cat-item > div.cat-descr > div.cat-name > a > div.cat-title").Text()
		p.Pict, _ = s.Find("div.product-block > div.cat-item > div.cat-pict > div.img-wrap > div > a.inner-link > div > div.item.active > img").Attr("src")
		p.Link, _ = s.Find("div.product-block > div.cat-item > div.cat-pict > div.img-wrap > div > a.inner-link").Attr("href")

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

		desc := make(map[string]string)
		docProd, _ := goquery.NewDocument(p.Link)
		docProd.Find("div.node-product.product > div.row > div.prod-descr-view > div.prod-fields > div.prod-field").Each(func(i int, dp *goquery.Selection) {
			descLabel := strings.TrimRight(strings.TrimSpace(dp.Find("span.field-label").Text()), ":")
			descContent := strings.TrimSpace(dp.Find("div.field-content").Text())
			if descLabel == "Артикул" {
				p.Sku = strings.TrimSpace(dp.Find("div.field-content.product-sku").Text())
			} else if !(descLabel == "") {
				desc[descLabel] = descContent
			}
		})
		tmpFullDesc := strings.TrimRight(strings.TrimSpace(docProd.Find("div.node-product.product > div.row > div.prod-descr-view > div.prod-text").Text()), ":")
		if (tmpFullDesc != "") || (tmpFullDesc != "_") {
			desc["Описание"] = strings.Replace(tmpFullDesc, "  ", "", -1)
		}
		p.Desc = desc

		// time.Sleep(1 * time.Second)
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

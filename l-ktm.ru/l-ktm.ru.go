/* Catalog parser for http://www.l-ktm.ru/ */
package main

import ( // {{{
	"encoding/json"
	"os"
	"path/filepath"
	//	"sort"
	"sync"
	//	"regexp"
	"fmt"

	"github.com/PuerkitoBio/goquery"
	//	"github.com/fiam/gounidecode/unidecode"
	//	"io"
	"io/ioutil"

	"golang.org/x/net/html/charset"
	//	"os"
	//	"path/filepath"
	"net/http"
	"net/url"
	"strconv"
	"strings"
	"time"
) // }}}

// Product structure
type Product struct { // {{{
	Sku      string `json:"sku"`
	Category string `json:"category"`
	Name     string `json:"name"`
	Desc     string `json:"desc"`
	Pict     string `json:"pict"`
	Link     string `json:"link"`
	//	Desc     map[string]string `json:"desc"`
} // }}}

// Products array
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
	failed            []string
	wg                sync.WaitGroup
	excludeList       = []string{
		"Новинки",
		"Распродажа",
		"...",
		"След.",
		//		"Все",
	}
) // }}}

const ( // {{{
	baseURL    = "http://www.l-ktm.ru"
	outputDir  = "images"
	outputJSON = "l-ktm.json"
) // }}}

func checkErr(msg string, err error) { // {{{
	if err != nil {
		fmt.Printf("%s - %s\n", msg, err)
	}
} // }}}

func getFilename(url string) string { // {{{
	tokens := strings.Split(url, "/")
	return tokens[len(tokens)-1]
} // }}}

func cleanPicURL(picUrl string) string { // {{{
	u, err := url.Parse(picUrl)
	checkErr("ERROR in cleanPicURL", err)
	newPicURL := fmt.Sprintf("%s://%s%s", u.Scheme, u.Host, u.Path)
	return newPicURL
} // }}}

/* func translit(s string) string { // {{{
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

// Set sleep time out for request
//func sleep(n time.Duration) {
//	time.Sleep(n * time.Second)
//}

func getPageDoc(url string) *goquery.Document { // {{{
	client := &http.Client{}
	req, err := http.NewRequest("GET", url, nil)
	checkErr("ERROR in getPageDoc::NewRequest", err)
	req.Header.Add("Accept-Encoding", "identity")
	req.Header.Set("User-Agent", "bvp Spider Bot v.0.1")
	req.Header.Set("Referer", "http://www.karapuzik37.ru/")
	req.Close = true

	response, err := client.Do(req)
	checkErr("ERROR in getPageDoc::Do", err)

	defer response.Body.Close()

	utf8, err := charset.NewReader(response.Body, response.Header.Get("Content-Type"))
	checkErr("Encoding error", err)

	//	body, err := ioutil.ReadAll(utf8)

	//	doc, err := goquery.NewDocumentFromResponse(response)
	doc, err := goquery.NewDocumentFromReader(utf8)
	//	doc, err := goquery.NewDocumentFromReader(response.Body)
	//	fmt.Printf("*** doc size - %d\n", doc.Size())
	//	dCode, _ := doc.Html()
	//	fmt.Printf("*** doc code - %d\n", dCode)
	checkErr("ERROR in getPageDoc::NewDocumentFromReader", err)
	return doc
} // }}}

func getCategories() map[string]string { // {{{
	fmt.Println("* Поиск категорий")
	//	reg, _ := regexp.Compile(`\s\([0-9]+\)`)

	doc := getPageDoc(baseURL)
	doc.Find("#left > ul > li:nth-child(2) > ul.first_level > li").Each(func(i int, s *goquery.Selection) {
		cat := strings.TrimSpace(s.Find("a").First().Text())
		catLink, _ := s.Find("a").First().Attr("href")
		if !contains(excludeList, cat) {
			//			fmt.Printf("%d '%s' => ,\n", i, cat)
			fmt.Printf("'%s' => %s,\n", cat, baseURL+catLink)
			categories[cat] = baseURL + catLink
			/* sdoc := getPageDoc(baseURL + catLink)
			sdoc.Find("ul.sublevel[style] > li").Each(func(j int, ss *goquery.Selection) {
				sCat := strings.TrimSpace(strings.Replace(ss.Find("a").First().Text(), "\n", "", -1))
				sCatLink, _ := ss.Find("a").Attr("href")
				//				fmt.Printf("'%s - %s' => %s,\n", cat, sCat, sCatLink)
				categories[cat+" - "+sCat] = baseURL + sCatLink
				//				if sCatLink[0] != '/' {
				//					//					fmt.Printf("'%s - %s' => %s,\n", cat, sCat, sCatLink)
				//					categories[cat+" - "+sCat] = sCatLink
				//				} else {
				//					//					fmt.Printf("'%s - %s' => %s,\n", cat, sCat, baseURL+sCatLink)
				//					categories[cat+" - "+sCat] = baseURL + sCatLink
				//				}
				// fmt.Printf("'%s - %s' => %s,\n", cat, sCat, baseURL+sCatLink)
				// fmt.Printf("'%s - %s' => %s,\n", cat, sCat, sCatLink)
				fmt.Printf("'%s - %s' => ,\n", cat, sCat)
				// categories[cat+" - "+sCat] = baseURL + sCatLink
				//				s.Find("div.cat_" + sCatID + " > div.cs:nth-child(1) > ul > li").Each(func(k int, sss *goquery.Selection) {
				//					ssCat := sss.Find("a").Text()
				//					ssCatLink, _ := sss.Find("a").Attr("href")
				//					if byte(ssCatLink[0]) == byte('/') {
				//						ssCatLink = baseURL + ssCatLink
				//					}
				//					//					fmt.Printf("'%s' - '%s' - '%s' => %s,\n", cat, sCat, ssCat, ssCatLink)
				//					//					fmt.Printf("'%s - %s' => ,\n", strings.TrimSpace(cat), ssCat)
				//					categories[strings.TrimSpace(cat)+" - "+sCat+" - "+ssCat] = ssCatLink
				//				})
			}) */
		}
	})
	//	sleep(2)
	fmt.Println("* Поиск категорий закончен")
	return categories
} // }}}

func getPages(url string) int { // {{{
	//	sleep(2)
	var ta []string
	doc, _ := goquery.NewDocument(url)
	doc.Find("ul.pagination > li").Each(func(i int, s *goquery.Selection) {
		// if !contains(excludeList, s.Text()) {
		if s.Text() != "" {
			// fmt.Printf("%d - %s\n", i, s.Text())
			ta = append(ta, s.Text())
		}
	})
	// last, _ = strconv.Atoi(doc.Find("#yw1 > li.page").Last().Text())
	if len(ta) > 0 {
		//		fmt.Printf("ta len - %s\n", ta[len(ta)-1])
		last, _ = strconv.Atoi(ta[len(ta)-1])
	} else {
		last = 1
	}
	//	if last = last; last == 0 {
	//		last = 1
	//	}
	//	fmt.Printf("Найдено страниц для %s - %d\n", url, last)
	return last
} // }}}

func walkinPages() { // {{{
	fmt.Printf("* Начинаем обработку страниц\n")
	for cat, catLink := range categories {
		// last := getPages(catLink)
		// for i := 1; i <= last; i++ {
		// currPage := fmt.Sprintf("%s%s%s", catLink, "?pageSize=160&ShopProduct_page=", strconv.Itoa(i))
		// currPage := fmt.Sprintf("%s%s%s", catLink, "?PAGEN_1=", strconv.Itoa(i))
		// fmt.Printf("** Обработка страницы %s - %s\n", cat, currPage)
		// doc, _ = goquery.NewDocument(currPage)
		// sleep(2)
		// fmt.Printf("\n** Обработка страницы %s - %s => ", cat, catLink)
		getProducts(getPageDoc(catLink), cat)
		// wg.Add(1)
		//}
	}
	// wg.Wait()
	fmt.Printf("\n* Закончили обработку страниц\n")
} // }}}

func getProducts(doc *goquery.Document, cat string) { // {{{
	//defer wg.Done()
	//	ret, _ := doc.Html()
	//	fmt.Printf("getProducts doc - %s\n", ret)
	doc.Find(`#CATALOG_div > div.CATALOG_ITEM`).Each(func(i int, s *goquery.Selection) {
		p := Product{}
		p.Category = cat

		tmpName, _ := s.Find("div.detail_frame > a").Attr("alt")
		p.Name = tmpName
		// tmpSku := strings.Replace(strings.Replace(s.Find("div.preview-picture > div.preview-info > div.preview-info-content > p").First().Text(), "\t", "", -1), "\n", "", -1)
		// p.Sku = strings.TrimLeft(tmpSku, "Артикул: ")
		p.Sku = p.Name

		link, _ := s.Find("div.detail_frame > a").Attr("href")
		// fmt.Printf("# Link - %s\n", link)
		p.Link = baseURL + link

		docp := getPageDoc(p.Link)

		tmpPict, _ := docp.Find("#alx_imagebox_div > div.alx_preview > a > img").Attr("src")
		// tmpPict, _ := s.Find("img.item-preview-image").First().Attr("src")
		p.Pict = baseURL + tmpPict

		// desc := make(map[string]string)
		//		desc := docp.Find("#fullDesc > p").Text()

		//		docp.Find("div.info_table_wrapper > table.info_table > tbody > tr").Each(func(i int, sd *goquery.Selection) {
		//			title := strings.TrimRight(sd.Find("td:nth-child(1)").Text(), ":")
		//			content := sd.Find("td:nth-child(2) > span").Text()
		//			desc[title] = content
		//		})
		//		sizes := ""
		//		docp.Find("div.size_list > ul > li").Each(func(j int, s *goquery.Selection) {
		//			if sizes == "" {
		//				sizes += s.Find("a").Text()
		//			} else {
		//				sizes += ", " + s.Find("a").Text()
		//			}
		//		})
		//		if len(sizes) > 0 {
		//			desc["Размерный ряд"] = sizes
		//		}

		//		p.Desc = desc

		if p.Sku == "" {
			failed = append(failed, "SKU: "+p.Sku+", Name: "+p.Name+", Category: "+p.Category)
			// bCode, _ := s.Html()
			// fmt.Printf("*** HTML - %s\n", bCode)
			doc.Next()
		} else {
			products = append(products, p)
			counter++
			fmt.Print(".")
		}
		//		pic, _ := s.Find("div.photo > div > a > img").Attr("src")
		//		fmt.Printf("*** Получена ссылка для %s - %s\n", cat, strings.Replace(link, "	", "", -1))
		//		fmt.Printf("*** Получена ссылка для %s - %s\n", cat, link)
		//		wg.Add(1)
		//		sleep(2)
		// getProductInfo(baseURL+link, cat)
	})
} // }}}

func getProductInfo(url string, cat string) { // {{{
	//	defer wg.Done()
	//	sleep(1)
	//	fmt.Printf("**** Обрабатываем продукт %s - %s\n", cat, url)
	docp := getPageDoc(url)

	p := Product{}
	tmpSKU := docp.Find("body > div.main > div.content > div.text_block > div > div > div.wh-block > div.card-block > div.card-block__right > div.card-block__sheet > table > tbody > tr:nth-child(1) > td.td_1").Text()
	if tmpSKU == "" {
		failed = append(failed, url)
		html, _ := docp.Html()
		fmt.Printf("%s - %s\n", url, html)
		return
	}
	p.Sku = strings.TrimLeft(strings.TrimSpace(tmpSKU), "Модель ")
	//	p.Sku = tmpSKU
	p.Name = docp.Find("#main > div.sheet > div.layout-wrapper.clearfix > div.content-layout > div.content-layout-row > div.layout-cell.content.clearfix > article.post > div.postcontent > div.productdetails-view > h1").Text()
	//	tmpPict, _ := docp.Find("#productMainPhoto").Attr("src")
	// tmpPict, _ := docp.Find("div.main-image > a[rel=vm-additional-images] > img").Attr("src")
	tmpPict, _ := docp.Find("div.main-image > a").Attr("href")
	// tmpPict, _ := docp.Find("body > div.main > div.content > div.text_block > div > div > div.wh-block > div.card-block > div.card-block__left > div.slider__b-pic > img").Attr("src")
	p.Pict = tmpPict
	p.Link = url
	p.Category = cat

	desc := make(map[string]string)
	docp.Find("body > div.main > div.content > div.text_block > div > div > div.wh-block > div.card-block > div.card-block__right > div.card-block__sheet > table > tbody > tr").Each(func(i int, sa *goquery.Selection) {
		title := strings.TrimSpace(sa.Find("td:nth-child(1)").Text())
		if !contains(excludeList, title) {
			desc[title] = strings.TrimSpace(sa.Find("td:nth-child(2)").Text())
			//		fmt.Printf("%s => %s\n", title, desc[title])
		}
	})
	//	p.Desc = desc

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

	dir, err := filepath.Abs(filepath.Dir(os.Args[0]))
	jsonProducts, _ := json.MarshalIndent(products, "", "  ")
	err = ioutil.WriteFile(dir+"/"+outputJSON, jsonProducts, 0644)
	if err != nil {
		panic(err)
	}
	fmt.Println("* Сбор страниц закончен")

	/* var cats []string
	for cat := range categories {
		cats = append(cats, cat)
	}
	sort.Strings(cats)

	for _, cat := range cats {
		fmt.Printf("%s => %s\n", cat, categories[cat])
	} */

	fmt.Printf("* Обработано продуктов %d\n", counter)
	if len(failed) > 0 {
		fmt.Printf("Не обработано - %d\n", len(failed))
		for _, fail := range failed {
			fmt.Printf("FAIL => %s\n", fail)
		}
	}
	te := time.Now()
	d := te.Sub(ts)
	fmt.Printf("Общее время выполнения в секундах - %d\n", int(d.Seconds()))
} // }}}
